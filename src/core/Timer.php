<?php

namespace rabbit\core;

/**
 * Class Timer
 * @package rabbit\core
 */
class Timer
{
    /**
     * 日志统计前缀
     */
    const TIMER_PREFIX = "timer";

    /**
     * @var array 所有定时器
     */
    private $timers = [];

    /**
     * 添加一个定时器，只执行一次
     *
     * @param string $name 名称
     * @param int $time 毫秒
     * @param callable $callback 回调函数
     * @param array $params 参数
     *
     * @return int
     */
    public function addAfterTimer(string $name, int $time, callable $callback, array $params = []): int
    {
        array_unshift($params, $name, $callback);
        $tid = \Swoole\Timer::after($time, [$this, 'timerCallback'], $params);
        $this->timers[$name][$tid] = $tid;
        return $tid;
    }

    /**
     * 添加一个定时器，每隔时间执行
     *
     * @param string $name 名称
     * @param int $time 毫秒
     * @param callable $callback 回调函数
     * @param    array $params 参数
     *
     * @return int
     */
    public function addTickTimer(string $name, int $time, callable $callback, array $params = []): int
    {
        array_unshift($params, $name, $callback);

        $tid = \Swoole\Timer::tick($time, [$this, 'timerCallback'], $params);

        $this->timers[$name][$tid] = $tid;

        return $tid;
    }

    /**
     * 移除一个定时器
     *
     * @param string $name 定时器名称
     *
     * @return bool
     */
    public function clearTimerByName(string $name): bool
    {
        if (!isset($this->timers[$name])) {
            return true;
        }
        foreach ($this->timers[$name] as $tid => $tidVal) {
            \Swoole\Timer::clear($tid);
        }
        unset($this->timers[$name]);

        return true;
    }

    /**
     * 定时器回调函数
     *
     * @param array $params 参数传递
     */
    public function timerCallback(int $timer_id, array $params = null): void
    {
        if (count($params) < 2) {
            return;
        }
        $name = array_shift($params);
        $callback = array_shift($params);

        $callbackParams = array_values($params);

        if (is_array($callback)) {
            list($class, $method) = $callback;
            $class->$method(...$callbackParams);
        } elseif ($callback instanceof \Closure) {
            call_user_func($callback, $callbackParams);
        } else {
            $callback(...$callbackParams);
        }
    }
}
