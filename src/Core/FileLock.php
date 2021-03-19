<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Closure;
use Rabbit\Base\App;
use Rabbit\Base\Contract\LockInterface;
use Rabbit\Base\Helper\ExceptionHelper;
use Throwable;

class FileLock implements LockInterface
{

    private static string $path = '/dev/shm/lock/';
    private static array $files = [];

    public function __invoke(Closure $function, bool $next = true, string $name = '', float $timeout = 600)
    {
        $name = empty($name) ? uniqid() : $name;
        try {
            if (!isset(self::$files[$name])) {
                self::$files[$name] = fopen(self::$path . $name, 'w');
            }
            flock(self::$files[$name], LOCK_EX);
            $res = call_user_func($function);
            flock(self::$files[$name], LOCK_UN);
            return $res;
        } catch (Throwable $throwable) {
            App::error(ExceptionHelper::dumpExceptionToString($throwable));
            flock(self::$files[$name], LOCK_UN);
        }
    }
}
