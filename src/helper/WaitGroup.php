<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/18
 * Time: 22:53
 */

namespace rabbit\helper;

use Swoole\Coroutine\Chanel;

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
     * @param callable $callback
     */
    public function add(callable $callback): self
    {
        $this->count++;
        go(function () use ($callback) {
            call_user_func($callback);
            $this->channel->push(true);
        });
    }

    /**
     * @param float $timeout
     */
    public function wait(float $timeout = 0): void
    {
        for ($i = 0; $i < $this->count; $i++) {
            $this->channel->pop($timeout);
        }
    }
}