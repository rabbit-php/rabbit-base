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
    private $channel;

    public function __construct()
    {
        $this->channel = makeChannel();
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
            $result = call_user_func($function);
            return $result;
        } catch (Throwable $throwable) {
            App::error(ExceptionHelper::dumpExceptionToString($throwable));
        } finally {
            !$this->channel->isEmpty() && $this->channel->pop();
        }
    }
}
