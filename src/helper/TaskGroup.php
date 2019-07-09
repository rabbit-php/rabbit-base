<?php


namespace rabbit\helper;


use Swoole\Coroutine\Channel;

class TaskGroup
{
    /** @var int */
    private $count = 0;

    /** @var \Swoole\Coroutine\Channel */
    private $channel;

    /**
     * CoroGroup constructor.
     */
    public function __construct()
    {
        $this->channel = new Channel;
    }

    /**
     * @return WaitGroup
     */
    public function create(): self
    {
        $this->channel = new Channel;
        return $this;
    }

    /**
     * @param string $name
     * @param callable $callback
     * @param callable|null $defer
     * @param mixed ...$params
     * @return WaitGroup
     */
    public function add(): self
    {
        $this->count++;
        return $this;
    }

    /**
     * @param $data
     */
    public function push($data): void
    {
        $this->channel->push($data);
    }

    /**
     * @param float $timeout
     * @return array
     */
    public function wait(float $timeout = 0): array
    {
        $res = [];
        for ($i = 0; $i < $this->count; $i++) {
            $res[] = $this->channel->pop($timeout);
        }
        $this->count = 0;
        return $res;
    }
}