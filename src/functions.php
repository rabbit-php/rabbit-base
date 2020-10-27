<?php

declare(strict_types=1);

use DI\NotFoundException;
use DI\DependencyException;
use Rabbit\Base\Core\Timer;
use Rabbit\Base\Core\Channel;
use Rabbit\Base\Core\Coroutine;
use Rabbit\Base\Core\WaitGroup;
use Rabbit\Base\Helper\LockHelper;
use Rabbit\Base\Core\ObjectFactory;
use Swow\Coroutine as SwowCoroutine;
use Rabbit\Base\Helper\ExceptionHelper;
use Swoole\Coroutine\Channel as CoroutineChannel;

static $loopList = [];

register_shutdown_function(function () {
    loopStop();
    Timer::clearTimers();
});

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
    function rgo(Closure $function)
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
                print_r(ExceptionHelper::dumpExceptionToString($throwable));
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
    function loop(Closure $function, string $name = null)
    {
        global $loopList;
        if ($name === null) {
            $name = uniqid();
        }
        $loopList[] = $name;

        $func = function () use ($function, &$loopList, $name) {
            while (in_array($name, $loopList)) {
                try {
                    $function();
                } catch (\Throwable $throwable) {
                    print_r(ExceptionHelper::dumpExceptionToString($throwable));
                }
            }
        };

        if (getCoEnv() === 1) {
            $co = new Coroutine($func);
            $co->resume();
            return $co;
        }
        return go($func);
    }
}

if (!function_exists('loopStop')) {
    /**
     * @author Albert <63851587@qq.com>
     * @param string $name
     * @return void
     */
    function loopStop(string $name = null): void
    {
        global $loopList;
        if ($name === null) {
            $loopList = [];
            return;
        }
        unset($loopList[array_search($name, $loopList)]);
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
        return ObjectFactory::createObject($type, $params, $singleTon);
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
     * @param Closure $function
     * @param string $key
     * @param float|int $timeout
     * @return mixed
     */
    function lock(string $name, Closure $function, string $key = '', float $timeout = 600)
    {
        $lock = LockHelper::getLock($name);
        return $lock($function, $key, $timeout);
    }
}

if (!function_exists('sync')) {
    function sync(&$value, Closure $function, float $timeout = 0.001): void
    {
        if ($value !== 0) {
            while ($value !== 0) {
                usleep(intval($timeout * 1000));
            }
            return;
        }
        $value++;
        $function();
        $value = 0;
    }
}

if (!function_exists('wgo')) {
    /**
     * @author Albert <63851587@qq.com>
     * @param Closure $function
     * @param float $timeout
     * @return boolean
     */
    function wgo(Closure $function, float $timeout = -1): bool
    {
        $wg = new WaitGroup();
        $wg->add(fn () => $function());
        return $wg->wait($timeout);
    }
}

if (!function_exists('wgeach')) {
    /**
     * @param array $data
     * @param Closure $function
     * @param float|int $timeout
     * @return bool
     * @throws Throwable
     */
    function wgeach(array &$data, Closure $function, float $timeout = -1): bool
    {
        $wg = new WaitGroup();
        foreach ($data as $key => $datum) {
            $wg->add(fn () => $function($key, $datum));
        }
        return $wg->wait($timeout);
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
        } else {
            return 1;
        }
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
            $context = Coroutine::getCurrent()->getContext();
        } else {
            $context = Co::getContext($id ?? Co::getCid());
        }
        return $context;
    }
}
