<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/20
 * Time: 10:52
 */

namespace rabbit\helper;

use Swoole\Coroutine\Channle;

/**
 * Class CSP
 * @package rabbit\helper
 */
class CSP
{
    /** @var \Swoole\Coroutine\Channle */
    private $channel;
    /** @var int */
    private $count = 0;

    /**
     * CSP constructor.
     * @param int $total
     */
    public function __construct(int $total)
    {
        $this->channel = new Channel($total);
    }

    /**
     * @param string $name
     * @param callable $callback
     */
    public function add(string $name, callable $callback): void
    {
        go(function () {
            $result = call_user_func($callback);
            $this->channel->push([$name, $result]);
        });
    }

    /**
     * @param float $timeout
     * @return array
     */
    public function wait(float $timeout = 0): array
    {
        $res = [];
        for ($i = 0; $i < $this->channel->capacity; $i++) {
            list($name, $result) = $this->channel->pop($timeout);
            $res[$name] = $result;
        }
        return $res;
    }
}