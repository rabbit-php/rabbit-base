<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/8
 * Time: 15:21
 */

use rabbit\helper\ExceptionHelper;

defined('BREAKS') or define('BREAKS', PHP_SAPI === 'cli' ? PHP_EOL : '</br>');

if (!function_exists('getDI')) {
    /**
     * @param string $name
     * @param bool $throwException
     * @param null $default
     * @return mixed|null
     * @throws Exception
     */
    function getDI(string $name, bool $throwException = true, $default = null)
    {
        return \rabbit\core\ObjectFactory::get($name, $throwException, $default);
    }
}

if (!function_exists('rgo')) {
    /**
     * @param Closure $function
     * @param Closure|null $defer
     * @return int
     * @throws Exception
     */
    function rgo(\Closure $function, ?\Closure $defer = null): int
    {
        return go(function () use ($function, $defer) {
            try {
                if (is_callable($defer)) {
                    defer($defer);
                }
                $function();
            } catch (\Throwable $throwable) {
                print_r(ExceptionHelper::convertExceptionToArray($throwable));
                return 0;
            }
        });
    }
}

if (!function_exists('waitGroup')) {
    /**
     * @return \rabbit\helper\WaitGroup
     */
    function waitGroup(): \rabbit\helper\WaitGroup
    {
        return \rabbit\helper\CoroHelper::createGroup();
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
        return \rabbit\core\ObjectFactory::has($key);
    }
}

if (!function_exists('hasDef')) {
    /**
     * @param string $key
     * @return bool
     */
    function hasDef(string $key): bool
    {
        return \rabbit\core\ObjectFactory::hasDef($key);
    }
}