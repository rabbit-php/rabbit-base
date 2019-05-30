<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/8
 * Time: 15:21
 */

defined('BREAKS') or define('BREAKS', PHP_SAPI === 'cli' ? PHP_EOL : '</br>');

if (!function_exists('getDI')) {
    function getDI(string $name, bool $throwException = true, $default = null)
    {
        return \rabbit\core\ObjectFactory::get($name, $throwException, $default);
    }
}

if (!function_exists('rgo')) {
    function rgo(\Closure $function, ?\Closure $defer = null): int
    {
        return \rabbit\helper\CoroHelper::go($function, $defer);
    }
}

if (!function_exists('waitGroup')) {
    function waitGroup(): \rabbit\helper\WaitGroup
    {
        return \rabbit\helper\CoroHelper::createGroup();
    }
}