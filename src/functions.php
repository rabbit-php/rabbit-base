<?php

declare(strict_types=1);

use ArrayObject as GlobalArrayObject;
use Rabbit\Base\Core\Channel;
use Rabbit\Base\Core\Coroutine;
use Rabbit\Base\Core\LoopControl;
use Rabbit\Base\Core\NumLock;
use Rabbit\Base\Helper\LockHelper;
use Rabbit\Base\Core\ObjectFactory;
use Rabbit\Base\Core\ShareResult;
use Rabbit\Base\Exception\InvalidConfigException;
use Swow\Coroutine as SwowCoroutine;
use Rabbit\Base\Helper\ExceptionHelper;
use Swoole\ArrayObject;
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
    function rgo(callable $function): int|Coroutine
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
            }
        });
    }
}

if (!function_exists('env')) {
    function env(string $key, $default = null): array|bool|string|null
    {
        if (($env = getenv($key)) !== false) {
            return $env;
        }
        return $default;
    }
}

if (!function_exists('loop')) {
    function loop(callable $function, int $micSleep = 1, LoopControl $ctrl = null, string $name = null): LoopControl
    {
        $ctrl = $ctrl ?? new LoopControl($micSleep, $name);
        $func = function () use ($function, $ctrl) {
            while ($ctrl->loop) {
                try {
                    $function();
                } catch (Throwable $throwable) {
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
    function create(string|array|callable $type, array $params = [], bool $singleTon = true): object
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
    function configure(object $object, iterable $config)
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

if (!function_exists('nlock')) {
    function nlock(callable $function, bool $next = true, string $key = '', float $timeout = 600)
    {
        $debug = current(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1));
        $name = "{$debug['file']}:{$debug['line']}";
        if (null === $lock = LockHelper::getLock($name)) {
            $lock = new NumLock();
            LockHelper::add($name, $lock);
        }
        return $lock($function, $next, $key, $timeout);
    }
}

if (!function_exists('sync')) {
    function sync(string $name, callable $function): void
    {
        static $arr = [];
        if (!isset($arr[$name])) {
            $arr[$name] = new Channel();
        }
        try {
            if ($arr[$name]->push($name)) {
                $function();
            }
        } catch (Throwable $e) {
            throw $e;
        } finally {
            if (isset($arr[$name])) {
                $arr[$name]->close();
                unset($arr[$name]);
            }
        }
    }
}

if (!function_exists('wgeach')) {
    function wgeach(array $data, callable $function, int $timeout = -1): array
    {
        if (count($data) === 0) {
            return $data;
        }
        if (getCoEnv() === 1) {
            $wf = new WaitReference();
            foreach ($data as $key => &$datum) {
                rgo(function () use ($function, $key, &$datum, $wf) {
                    $datum = $function($key, $datum, $wf);
                });
            }
            WaitReference::wait($wf, $timeout);
        } else {
            $wg = new CoroutineWaitGroup(count($data));
            foreach ($data as $key => &$datum) {
                rgo(function () use ($function, $key, &$datum, $wg) {
                    $datum = $function($key, $datum);
                    $wg->done();
                });
            }
            $wg->wait($timeout);
        }
        return $data;
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

if (!function_exists('getContext')) {
    function getContext(int $id = null): ArrayObject|GlobalArrayObject
    {
        if (getCoEnv() === 1) {
            return Coroutine::getCurrent()->getContext();
        }
        return Co::getContext($id ?? Co::getCid());
    }
}

if (!function_exists('waitGroup')) {
    function waitGroup(int $n = 0): WaitGroup|CoroutineWaitGroup
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
        $lock = getCoEnv() === 0 && is_array(\Co::getOptions()) && (\Co::getOptions()['enable_preemptive_scheduler'] ?? false & $enable);
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

if (!function_exists('share')) {
    function share(string $key, callable $func, int $timeout = 3): ShareResult
    {
        return ShareResult::getShare($key, $timeout)($func);
    }
}
