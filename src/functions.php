<?php

declare(strict_types=1);

use ArrayObject as GlobalArrayObject;
use Rabbit\Base\App;
use Rabbit\Base\Core\Channel;
use Rabbit\Base\Core\Coroutine;
use Rabbit\Base\Core\LoopControl;
use Rabbit\Base\Core\ShareResult;
use Rabbit\Base\DI\ArrayDefinition;
use Rabbit\Base\DI\Definition;
use Swow\Coroutine as SwowCoroutine;
use Rabbit\Base\Helper\ExceptionHelper;
use Swoole\ArrayObject;
use Swoole\Coroutine as SwooleCoroutine;
use Swoole\Coroutine\WaitGroup as CoroutineWaitGroup;
use Swow\Sync\WaitGroup;
use Swow\Sync\WaitReference;

if (!function_exists('env')) {
    function env(string $name, mixed $default = null): array|bool|string|int|null
    {
        if (!isset($_ENV[$name]) && !isset($_SERVER[$name])) {
            return $default;
        }
        $env = $_ENV[$name] ?? $_SERVER[$name];
        switch ($env) {
            case 'true':
                return true;
            case 'false':
                return false;
            case 'null':
                return null;
            case is_numeric($env):
                return (int)$env;
            default:
                return $env;
        };
    }
}

if (!function_exists('config')) {
    function config(string $name, mixed $default = null): mixed
    {
        return App::$di->config[$name] ?? $default;
    }
}

if (!function_exists('service')) {
    function service(string $name, bool $throwException = true, mixed $default = null): ?object
    {
        return App::$di->get($name, $throwException, $default);
    }
}

if (!function_exists('arrdef')) {
    function arrdef(array $items): ArrayDefinition
    {
        return new ArrayDefinition($items);
    }
}

if (!function_exists('definition')) {
    function definition(string $name): Definition
    {
        return new Definition($name);
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
                if (config('debug')) {
                    App::error(ExceptionHelper::dumpExceptionToString($throwable));
                } else {
                    App::error($throwable->getMessage() . PHP_EOL);
                }
            }
        });
    }
}

if (!function_exists('loop')) {
    function loop(callable $function, int $micSleep = 1, LoopControl $ctrl = null, string $name = null): LoopControl
    {
        $ctrl = $ctrl ?? new LoopControl($micSleep, $name);
        $func = function () use ($function, $ctrl): void {
            while ($ctrl->loop) {
                try {
                    $function();
                } catch (Throwable $throwable) {
                    if (config('debug')) {
                        App::error(ExceptionHelper::dumpExceptionToString($throwable));
                    } else {
                        App::error($throwable->getMessage() . PHP_EOL);
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
        return schedule([App::$di, 'createObject'], $type, $params, $singleTon);
    }
}

if (!function_exists('configure')) {
    function configure(object $object, iterable $config): void
    {
        App::$di->configure($object, $config);
    }
}

if (!function_exists('sync')) {
    function sync(string $name, callable $function, bool $once = false): void
    {
        static $sync = [];
        if (!isset($sync[$name])) {
            $channel = new Channel();
            $sync[$name] = $channel;
        } else {
            $channel = $sync[$name];
        }
        try {
            if ($channel->push($name)) {
                $function();
            }
        } catch (Throwable $e) {
            throw $e;
        } finally {
            if ($channel->isEmpty() || $once) {
                $channel->close();
                unset($sync[$name]);
            } else {
                $channel->pop();
            }
        }
    }
}

if (!function_exists('wgeach')) {
    function wgeach(array &$data, callable $function, int $timeout = -1): void
    {
        if (count($data) === 0) {
            return;
        }
        if (getCoEnv() === 1) {
            $wf = new WaitReference();
            foreach ($data as $key => &$datum) {
                rgo(function () use ($function, $key, &$datum, $wf): void {
                    $function($key, $datum, $wf);
                });
            }
            WaitReference::wait($wf, $timeout);
        } else {
            $wg = new CoroutineWaitGroup(count($data));
            foreach ($data as $key => &$datum) {
                rgo(function () use ($function, $key, &$datum, $wg): void {
                    $function($key, $datum);
                    $wg->done();
                });
            }
            $wg->wait($timeout);
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

if (!function_exists('getContext')) {
    function getContext(int $id = null): null|ArrayObject|GlobalArrayObject
    {
        if (getCoEnv() === 1) {
            return Coroutine::getCurrent()->getContext();
        }
        return Co::getContext($id ?? Co::getCid());
    }
}

if (!function_exists('waitGroup')) {
    function waitGroup(int $n = 0): null|WaitGroup|CoroutineWaitGroup
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

if (!function_exists('schedule')) {
    function schedule(callable $callback, ...$arg): mixed
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

if (!function_exists('ryield')) {
    function ryield(mixed $data = null): mixed
    {
        if (getCoEnv() === 1) {
            return Coroutine::getCurrent()->yield($data);
        } else {
            return SwooleCoroutine::yield();
        }
    }
}

if (!function_exists('resume')) {
    function resume(array|int $data): mixed
    {
        if (getCoEnv() === 1) {
            return Coroutine::getCurrent()->resume($data);
        } else {
            return SwooleCoroutine::resume($data);
        }
    }
}

if (!function_exists('cancel')) {
    function cancel(int $cid = null): void
    {
        if (getCoEnv() === 1) {
            Coroutine::getCurrent()->kill();
        } else {
            SwooleCoroutine::cancel($cid);
        }
    }
}
