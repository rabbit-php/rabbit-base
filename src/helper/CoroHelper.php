<?php

namespace rabbit\helper;

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
}