<?php

namespace rabbit\core;

use rabbit\helper\CoroHelper;

/**
 * Class TimerCo
 * @package rabbit\core
 */
class TimerCo
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
     * @param string $name
     * @param float $time
     * @param callable $callback
     * @param array $params
     * @return int
     */
    public function addAfterTimer(string $name, float $time, callable $callback, array $params = []): int
    {
        array_unshift($params, $name ?? uniqid(), self::TYPE_AFTER, $callback);
        $this->timers[$name] = ['name' => $name, 'type' => self::TYPE_AFTER];
        $tid = go(function () use ($time, $params) {
            CoroHelper::sleep($time / 1000);
            $this->timerCallback($params);
        });
        $this->timers[$name]['tid'] = $tid;
        return $tid;
    }

    /**
     * @param string $name
     * @param float $time
     * @param callable $callback
     * @param array $params
     * @return int
     */
    public function addTickTimer(string $name, float $time, callable $callback, array $params = []): int
    {
        array_unshift($params, $name ?? uniqid(), self::TYPE_AFTER, $callback);
        $this->timers[$name] = ['name' => $name, 'type' => self::TYPE_AFTER];
        $tid = go(function () use ($name, $time, $params) {
            while (isset($this->timers[$name])) {
                $this->timerCallback($params);
                CoroHelper::sleep($time / 1000);
            }
        });
        $this->timers[$name]['tid'] = $tid;
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
        unset($this->timers[$name]);
        return true;
    }

    /**
     * @return bool
     */
    public function clearTimers(): bool
    {
        $this->timers = [];
        return true;
    }

    /**
     * 定时器回调函数
     *
     * @param array $params 参数传递
     */
    public function timerCallback(array $params): void
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
