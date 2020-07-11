<?php

use DI\DependencyException;
use DI\NotFoundException;

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
        return \Rabbit\Base\Core\ObjectFactory::get($name, $throwException, $default);
    }
}

if (!function_exists('rgo')) {
    /**
     * @param Closure $function
     * @param Closure|null $defer
     * @return int
     */
    function rgo(\Closure $function, ?\Closure $defer = null): int
    {
        return go(function () use ($function, $defer) {
            try {
                if (is_callable($defer)) {
                    defer($defer);
                }
                return $function();
            } catch (\Throwable $throwable) {
                print_r(\Rabbit\Base\Helper\ExceptionHelper::convertExceptionToArray($throwable));
                return 0;
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

if (!function_exists('hasDI')) {
    /**
     * @param string $key
     * @return bool
     */
    function hasDI(string $key): bool
    {
        return \Rabbit\Base\Core\ObjectFactory::has($key);
    }
}

if (!function_exists('hasDef')) {
    /**
     * @param string $key
     * @return bool
     */
    function hasDef(string $key): bool
    {
        return \Rabbit\Base\Core\ObjectFactory::hasDef($key);
    }
}

if (!function_exists('loop')) {
    /**
     * @param Closure $function
     * @return int
     */
    function loop(\Closure $function): int
    {
        return go(function () use ($function) {
            while (true) {
                try {
                    $function();
                } catch (\Throwable $throwable) {
                    print_r(\Rabbit\Base\Helper\ExceptionHelper::convertExceptionToArray($throwable));
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
        return \Rabbit\Base\Core\ObjectFactory::createObject($type, $params, $singleTon);
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
        \Rabbit\Base\Core\ObjectFactory::configure($object, $config);
    }
}

if (!function_exists('lock')) {
    /**
     * @param string $name
     * @param Closure $function
     * @param string $key
     * @param float|int $timeout
     * @param array $params
     * @return mixed
     */
    function lock(string $name, Closure $function, string $key = '', float $timeout = 600, array $params = [])
    {
        $lock = \Rabbit\Base\Helper\LockHelper::getLock($name);
        return $lock($function, $key, $timeout, $params);
    }
}

if (!function_exists('sync')) {
    /**
     * @param Closure $function
     * @return mixed
     */
    function sycn(Closure $function)
    {
        $flags = \Swoole\Runtime::getHookFlags();
        \Swoole\Runtime::enableCoroutine(false);
        $result = $function();
        \Swoole\Runtime::enableCoroutine($flags);
        return $result;
    }
}
