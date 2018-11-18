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

    public function __construct()
    {
        $this->channel = new Channel;
    }

    public function add(): void
    {
        $this->count++;
    }

    public function done(): void
    {
        $this->channel->push(true);
    }

    public function wait():void
    {
        for ($i = 0; $i < $this->count; $i++) {
            $this->channel->pop();
        }
    }
}