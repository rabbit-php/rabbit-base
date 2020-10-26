<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Closure;
use Throwable;
use Rabbit\Base\App;
use Rabbit\Base\Contract\LockInterface;
use Rabbit\Base\Helper\ExceptionHelper;

class NumLock implements LockInterface
{
    private int $num = 0;
    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-30
     * @param \Closure $function
     * @param string $name
     * @param float $timeout
     * @return void
     */
    public function __invoke(Closure $function, string $name = '', float $timeout = 0.001)
    {
        try {
            while ($this->num !== 0) {
                usleep(intval($timeout * 1000));
            }
            $this->num++;
            return call_user_func($function);
        } catch (Throwable $throwable) {
            App::error(ExceptionHelper::dumpExceptionToString($throwable));
        } finally {
            $this->num = 0;
        }
    }
}
