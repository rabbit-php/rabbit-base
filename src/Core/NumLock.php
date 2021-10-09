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
    private Channel $channel;

    public function __construct()
    {
        $this->channel = new Channel();
    }


    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-09-30
     * @param \Closure $function
     * @param string $name
     * @param float $timeout
     * @return void
     */
    public function __invoke(Closure $function, bool $next = true, string $name = '', float $timeout = 0.01)
    {
        if ($this->channel->isFull() && !$next) {
            return;
        }
        $this->channel->push(1, $timeout);
        try {
            return call_user_func($function);
        } catch (Throwable $throwable) {
            App::error(ExceptionHelper::dumpExceptionToString($throwable));
        } finally {
            !$this->channel->isEmpty() && $this->channel->pop();
        }
    }
}
