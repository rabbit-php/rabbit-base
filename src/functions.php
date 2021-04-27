<?php

declare(strict_types=1);

use DI\NotFoundException;
use DI\DependencyException;
use Rabbit\Base\Core\Timer;
use Rabbit\Base\Core\Channel;
use Rabbit\Base\Core\Coroutine;
use Rabbit\Base\Core\LoopControl;
use Rabbit\Base\Helper\LockHelper;
use Rabbit\Base\Core\ObjectFactory;
use Rabbit\Base\Exception\InvalidConfigException;
use Swow\Coroutine as SwowCoroutine;
use Rabbit\Base\Helper\ExceptionHelper;
use Swoole\Coroutine\Channel as CoroutineChannel;
use Swoole\Coroutine\WaitGroup as CoroutineWaitGroup;
use Swow\Sync\WaitGroup;
use Swow\Sync\WaitReference;

if (!function_exists('getDI')) {
    /**
     * @param string $name
     * @param bool $throwException
     * @param null $default
     * @return mixed|null
     * @throws Throwable
     */
    function getDI(string $name, bool $throwException = true, $default = null)
    {
        return ObjectFactory::get($name, $throwException, $default);
    }
}

if (!function_exists('rgo')) {
    function rgo(callable $function)
    {
        if (getCoEnv() === 1) {
            $co = new Coroutine($function);
            $co->resume();
            return $co;
        }
        return go(function () use ($function): void {
            try {
                $function();
            } catch (\Throwable $throwable) {
                if (getDI('debug')) {
                    fwrite(STDOUT, ExceptionHelper::dumpExceptionToString($throwable));
                } else {
                    fwrite(STDOUT, $throwable->getMessage() . PHP_EOL);
                }
            } finally {
                gc_collect_cycles();
            }
        });
    }
}

if (!function_exists('env')) {
    /**
     * @param string $key
     * @param null $default
     * @return array|false|string|null
     */
    function env(string $key, $default = null)
    {
        if (($env = getenv($key)) !== false) {
            return $env;
        }
        return $default;
    }
}

if (!function_exists('loop')) {
    function loop(callable $function, int &$micSleep = 1, string $name = null)
    {
        $ctrl = new LoopControl($micSleep, $name);
        $func = function () use ($function, $ctrl, $micSleep, $name) {
            while (true) {
                try {
                    $function();
                } catch (\Throwable $throwable) {
                    if (getDI('debug')) {
                        fwrite(STDOUT, ExceptionHelper::dumpExceptionToString($throwable));
                    } else {
                        fwrite(STDOUT, $throwable->getMessage() . PHP_EOL);
                    }
                } finally {
                    if ($ctrl->sleep > 0) {
                        usleep($ctrl->sleep * 1000);
                    }
                }
            }
        };

        if (getCoEnv() === 1) {
            $co = new Coroutine($func);
            $co->resume();
            return $co;
        }
        $ctrl->setCid(go($func));
        return $ctrl;
    }
}

if (!function_exists('create')) {
    /**
     * @param $type
     * @param array $params
     * @param bool $singleTon
     * @return mixed
     * @throws DependencyException
     * @throws ReflectionException|NotFoundException
     */
    function create($type, array $params = [], bool $singleTon = true)
    {
        return schedule(ObjectFactory::class . '::createObject', $type, $params, $singleTon);
    }
}

if (!function_exists('configure')) {
    /**
     * @param $object
     * @param iterable $config
     * @throws ReflectionException
     */
    function configure($object, iterable $config)
    {
        ObjectFactory::configure($object, $config);
    }
}

if (!function_exists('lock')) {
    /**
     * @param string $name
     * @param callable $function
     * @param string $key
     * @param float|int $timeout
     * @return mixed
     */
    function lock(string $name, callable $function, bool $next = true, string $key = '', float $timeout = 600)
    {
        if (null === $lock = LockHelper::getLock($name)) {
            throw new InvalidConfigException("lock name $name not exists!");
        }
        return $lock($function, $next, $key, $timeout);
    }
}

if (!function_exists('sync')) {
    function sync(&$value, callable $function, float $timeout = 0.001): void
    {
        if ($value !== 0) {
            while ($value !== 0) {
                usleep(intval($timeout * 1000 * 1000));
            }
            return;
        }
        $value++;
        try {
            $function();
        } catch (Throwable $e) {
            throw $e;
        } finally {
            $value = 0;
        }
    }
}

if (!function_exists('wgo')) {
    function wgo(callable $function, int $timeout = -1): bool
    {
        if (getCoEnv() === 1) {
            $wf = new WaitReference();
            rgo(function () use ($function, $wf) {
                $function($wf);
            });
            WaitReference::wait($wf, $timeout);
            return true;
        } else {
            $wg = new CoroutineWaitGroup(1);
            rgo(function () use ($function, $wg) {
                $function();
                $wg->done();
            });
            return $wg->wait($timeout);
        }
    }
}

if (!function_exists('wgeach')) {
    function wgeach(array &$data, callable $function, int $timeout = -1): bool
    {
        if (count($data) === 0) {
            return false;
        }
        if (getCoEnv() === 1) {
            $wf = new WaitReference();
            foreach ($data as $key => $datum) {
                rgo(function () use ($function, $key, $datum, $wf) {
                    $function($key, $datum, $wf);
                });
            }
            WaitReference::wait($wf, $timeout);
            return true;
        } else {
            $wg = new CoroutineWaitGroup(count($data));
            foreach ($data as $key => $datum) {
                rgo(function () use ($function, $key, $datum, $wg) {
                    $function($key, $datum);
                    $wg->done();
                });
            }
            return $wg->wait($timeout);
        }
    }
}

if (!function_exists('getRootId')) {
    /**
     * @return int
     */
    function getRootId(): int
    {
        if (getCoEnv() === 1) {
            return Coroutine::getMain()->getId();
        }
        $cid = Co::getCid();
        while (true) {
            $pid = Co::getPcid($cid);
            if ($pid === false || $pid === -1) {
                return $cid;
            }
            $cid = $pid;
        }
    }
}

if (!function_exists('getCid')) {
    function getCid(): int
    {
        if (extension_loaded('swoole')) {
            return Co::getCid();
        }
        if (extension_loaded('swow') && $co = SwowCoroutine::getCurrent()) {
            return $co ? $co->getId() : -1;
        }
        return -1;
    }
}

if (!function_exists('getCoEnv')) {
    function getCoEnv(): int
    {
        if (extension_loaded('swoole') && (-1 !== Co::getCid())) {
            return 0;
        }
        return 1;
    }
}

if (!function_exists('makeChannel')) {
    function makeChannel(int $size = 0)
    {
        return getCoEnv() === 1 ? new Channel($size) : new CoroutineChannel($size);
    }
}

if (!function_exists('getContext')) {
    function getContext(int $id = null)
    {
        if (getCoEnv() === 1) {
            return Coroutine::getCurrent()->getContext();
        }
        return Co::getContext($id ?? Co::getCid());
    }
}

if (!function_exists('waitGroup')) {
    function waitGroup(int $n = 0)
    {
        if (getCoEnv() === 1) {
            $wg = new WaitGroup();
            $n && $wg->add($n);
            return $wg;
        }
        return new CoroutineWaitGroup($n);
    }
}

if (!function_exists('waitReference')) {
    function waitReference(callable $func, int $timeout = -1): void
    {
        $wf = new WaitReference();
        $func($wf);
        WaitReference::wait($wf, $timeout);
    }
}

if (!function_exists('str_starts_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}

if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        return $needle === '' || substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }
}

if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return strpos($haystack, $needle) !== false;
    }
}

if (!function_exists('schedule')) {
    function schedule(callable $callback, ...$arg)
    {
        static $enable = true;
        $options = \Co::getOptions();
        $lock = getCoEnv() === 0 && is_array($options) && isset($options['enable_preemptive_scheduler']) && $options['enable_preemptive_scheduler'] && $enable;
        if ($lock) {
            \Co::disableScheduler();
            $enable = false;
        }
        $res = call_user_func($callback, ...$arg);
        if ($lock) {
            $enable = true;
            \Co::enableScheduler();
        }
        return $res;
    }
}
