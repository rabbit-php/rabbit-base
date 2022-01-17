<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Swoole\Coroutine\Channel as CoroutineChannel;
use Swow\Channel as SwowChannel;
use Throwable;

final class Channel
{
    protected readonly Channel|CoroutineChannel $channel;

    public function __construct(int $capacity = 0)
    {
        $this->channel = getCoEnv() === 1 ? new SwowChannel($capacity) : new CoroutineChannel($capacity);
    }

    public function __call($name, $arguments)
    {
        return $this->channel->$name(...$arguments);
    }

    public function __get($name)
    {
        return $this->channel->$name;
    }

    public function __set($name, $value)
    {
        $this->channel->$name = $value;
    }

    public function pop(int $timeout = -1)
    {
        if (getCoEnv() === 1) {
            $timeout > 0 && $timeout *= 1000;
        }
        try {
            return $this->channel->pop($timeout);
        } catch (Throwable $e) {
            return false;
        }
    }
}
