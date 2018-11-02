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

    const TYPE_AFTER = 'after';
    const TYPE_TICKET = 'tick';

    /**
     * @var array 所有定时器
     */
    private $timers = [];

    /**
     * @return array
     */
    public function getTimers(): array
    {
        return $this->timers;
    }

    /**
     * @param string $name
     * @param null $default
     * @return array
     */
    public function getTimer(string $name, $default = null): array
    {
        return isset($this->timers[$name]) ? $this->timers[$name] : $default;
    }

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
        array_unshift($params, $name, self::TYPE_AFTER, $callback);
        $tid = \Swoole\Timer::after($time, [$this, 'timerCallback'], $params);
        $this->timers[$name] = ['name' => $name, 'tid' => $tid, 'type' => self::TYPE_AFTER, 'count' => 0];
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
        array_unshift($params, $name, self::TYPE_TICKET, $callback);

        $tid = \Swoole\Timer::tick($time, [$this, 'timerCallback'], $params);

        $this->timers[$name] = ['name' => $name, 'tid' => $tid, 'type' => self::TYPE_TICKET, 'count' => 0];

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
        $type = array_shift($params);
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
