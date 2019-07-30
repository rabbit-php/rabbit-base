<?php

namespace rabbit\core;

use rabbit\contract\AbstractTimer;

/**
 * Class Timer
 * @package rabbit\core
 */
class Timer extends AbstractTimer
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
        $tid = \Swoole\Timer::after($time, [$this, 'timerCallback'], $params);
        $this->timers[$name] = ['name' => $name, 'tid' => $tid, 'type' => self::TYPE_AFTER, 'count' => 0];
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
        \Swoole\Timer::clear($this->timers[$name]['tid']);
        unset($this->timers[$name]);

        return true;
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
        array_unshift($params, $name ?? uniqid(), self::TYPE_TICKET, $callback);

        $tid = \Swoole\Timer::tick($time, [$this, 'timerCallback'], $params);

        $this->timers[$name] = ['name' => $name, 'tid' => $tid, 'type' => self::TYPE_TICKET, 'count' => 0];

        return $tid;
    }

    /**
     * @return bool
     */
    public function clearTimers(): bool
    {
        foreach ($this->timers as $name => $timerData) {
            $this->clearTimerByName($name);
        }
        return true;
    }

    /**
     * @param int $timer_id
     * @param array|null $params
     */
    public function timerCallback(int $timer_id, array $params = null): void
    {
        $this->run($params);
    }
}
