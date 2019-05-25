<?php

namespace rabbit\core;

use rabbit\contract\AbstractTimer;
use rabbit\helper\CoroHelper;

/**
 * Class TimerCo
 * @package rabbit\core
 */
class TimerCo extends AbstractTimer
{
    /**
     * @param string $name
     * @param float $time
     * @param callable $callback
     * @param array $params
     * @return int
     */
    public function addAfterTimer(string $name, float $time, callable $callback, array $params = []): int
    {
        $this->clearTimerByName($name);
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
     * 定时器回调函数
     *
     * @param array $params 参数传递
     */
    public function timerCallback(array $params): void
    {
        $this->run($params);
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
        $this->clearTimerByName($name);
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
     * @return bool
     */
    public function clearTimers(): bool
    {
        $this->timers = [];
        return true;
    }
}
