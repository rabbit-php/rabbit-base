<?php

namespace rabbit\helper;

use rabbit\App;

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
     * @param int $total
     * @return CSP
     */
    public static function createCsp(int $total): CSP
    {
        return new CSP($total);
    }

    /**
     * @param \Closure $function
     * @throws \Exception
     */
    public static function go(\Closure $function)
    {
        try {
            go(function () use ($function) {
                $function();
            });
        } catch (\Throwable $throwable) {
            App::error($throwable->getMessage());
        }
    }
}