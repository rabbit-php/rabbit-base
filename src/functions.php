<?php
declare(strict_types=1);

use DI\DependencyException;
use DI\NotFoundException;
use Rabbit\Base\Core\ObjectFactory;
use Rabbit\Base\Core\WaitGroup;
use Rabbit\Base\Helper\ExceptionHelper;
use Rabbit\Base\Helper\LockHelper;
use Swoole\Runtime;

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
    /**
     * @param Closure $function
     * @param Closure|null $defer
     * @return int
     * @throws Throwable
     */
    function rgo(Closure $function, ?Closure $defer = null): int
    {
        return go(function () use ($function, $defer): void {
            try {
                if (is_callable($defer)) {
                    defer($defer);
                }
                $function();
            } catch (\Throwable $throwable) {
                print_r(ExceptionHelper::convertExceptionToArray($throwable));
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
    /**
     * @param Closure $function
     * @return int
     * @throws Throwable
     */
    function loop(Closure $function): int
    {
        return go(function () use ($function) {
            while (true) {
                try {
                    $function();
                } catch (\Throwable $throwable) {
                    print_r(ExceptionHelper::convertExceptionToArray($throwable));
                }
            }
        });
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
    /**
     * @param Closure $function
     * @return mixed
     */
    function sync(Closure $function)
    {
        $flags = Runtime::getHookFlags();
        Runtime::enableCoroutine(false);
        $result = $function();
        Runtime::enableCoroutine($flags);
        return $result;
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
            $wg->add(fn() => $function($key, $datum));
        }
        return $wg->wait($timeout);
    }
}
