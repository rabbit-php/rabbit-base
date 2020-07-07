<?php

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
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    function create($type, array $params = [], bool $singleTon = true)
    {
        return \rabbit\core\ObjectFactory::createObject($type, $params, $singleTon);
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
        return \Rabbit\Base\Core\ObjectFactory::configure($object, $config);
    }
}
