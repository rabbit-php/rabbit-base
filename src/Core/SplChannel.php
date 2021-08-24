<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use SplQueue;
use Swoole\Coroutine;

class SplChannel
{
    protected SplQueue $channel;
    protected SplQueue $wait;

    public function __construct()
    {
        $this->channel = new SplQueue();
        $this->wait = new SplQueue();
    }

    public function push($item): bool
    {
        $this->channel->push($item);
        if ($this->wait->count() > 0) {
            Coroutine::resume($this->wait->dequeue());
        }
        return true;
    }

    public function pop()
    {
        if (!$this->channel->isEmpty()) {
            return $this->channel->dequeue();
        }
        $this->wait->push(Coroutine::getCid());
        Coroutine::yield();
        return $this->channel->dequeue();
    }
}
