<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/18
 * Time: 22:53
 */

namespace rabbit\helper;

use Swoole\Coroutine\Channel;

/**
 * Class WaitGroup
 * @package rabbit\helper
 */
class WaitGroup
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
    public function add(string $name, callable $callback, ?callable $defer = null, ...$params): self
    {
        $this->count++;
        go(function () use ($name, $callback, $defer, $params) {
            if (is_callable($defer)) {
                $defer();
            }
            try {
                $result = call_user_func_array($callback, $params);
            } catch (\Throwable $throwable) {
                $result = $throwable;
            } finally {
                $this->channel->push([$name, $result]);
            }
        });
        return $this;
    }

    /**
     * @param float $timeout
     * @return array
     */
    public function wait(float $timeout = 0): array
    {
        try {
            $res = [];
            for ($i = 0; $i < $this->count; $i++) {
                list($name, $result) = $this->channel->pop($timeout);
                $res[$name] = $result;
            }
            return $res;
        } finally {
            $this->count = 0;
        }
    }
}