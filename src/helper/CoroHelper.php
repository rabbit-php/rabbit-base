<?php

namespace rabbit\helper;

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
     * @return int
     */
    public static function getPid(): int
    {
        if (PHP_SAPI === 'cli' && is_callable('\Swoole\Coroutine::getuid')) {
            return \Swoole\Coroutine::getPcid() > 0 ? \Swoole\Coroutine::getPcid() : 0;
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
     * @return WaitGroup
     */
    public static function createGroup(): WaitGroup
    {
        return new WaitGroup();
    }

    /**
     * @param \Closure $function
     * @param \Closure|null $defer
     * @return int
     * @throws \Exception
     */
    public static function go(\Closure $function, ?\Closure $defer = null): int
    {
        return go(function () use ($function, $defer) {
            try {
                if (is_callable($defer)) {
                    $defer();
                }
                $function();
            } catch (\Throwable $throwable) {
                print_r(ExceptionHelper::convertExceptionToArray($throwable));
                return 0;
            }
        });
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