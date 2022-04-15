<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Rabbit\Base\Exception\NotSupportedException;
use SplQueue;

final class SplChannel
{
    protected SplQueue $channel;
    protected SplQueue $wait;

    public function __construct()
    {
        $this->channel = new SplQueue();
        $this->wait = new SplQueue();
    }

    public function __call($name, $arguments)
    {
        if (method_exists($this->channel, $name)) {
            switch ($name) {
                case 'push':
                case 'enqueue':
                case 'unshift':
                    return $this->add($name, ...$arguments);
                case 'pop':
                case 'dequeue':
                case 'shift':
                    return $this->del($name);
            }
            return $this->channel->$name(...$arguments);
        }
        throw new NotSupportedException("SplQueue not support method $name");
    }

    private function add(string $method, $item): void
    {
        $this->channel->$method($item);
        if ($this->wait->count() > 0) {
            resume((int)$this->wait->dequeue());
        }
    }

    private function del(string $method)
    {
        if (!$this->channel->isEmpty()) {
            return $this->channel->$method();
        }
        $this->wait->enqueue(getCid());
        ryield();
        return $this->channel->$method();
    }
}
