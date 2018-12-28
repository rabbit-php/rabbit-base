<?php

namespace rabbit\helper;

use rabbit\App;
use rabbit\contract\DeferInterface;

/**
 * Class CoroHelper
 * @package rabbit\helper
 */
class CoroHelper
{
    /**
     * @return int
     */
    public static function getId(): int
    {
        if (PHP_SAPI === 'cli' && is_callable('\Swoole\Coroutine::getuid')) {
            return \Swoole\Coroutine::getuid() > 0 ? \Swoole\Coroutine::getuid() : 0;
        } else {
            return 0;
        }
    }

    /**
     * @param float $mictime
     */
    public static function sleep(float $mictime): void
    {
        \Swoole\Coroutine::sleep($mictime);
    }

    /**
     * @return CoroGroup
     */
    public static function createGroup(): WaitGroup
    {
        return new WaitGroup();
    }

    /**
     * @param \Closure $function
     * @throws \Exception
     */
    public static function go(\Closure $function): int
    {
        try {
            return go(function () use ($function) {
                $function();
            });
        } catch (\Throwable $throwable) {
            App::error($throwable->getMessage());
            return 0;
        }
    }

    /**
     * @param callable $function
     */
    public static function addDefer(callable $function): void
    {
        defer(function () use ($function) {
            call_user_func($function);
        });
    }
}