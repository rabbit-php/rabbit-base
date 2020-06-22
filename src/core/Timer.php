<?php

namespace rabbit\core;

use Co\Channel;
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
    public static function addAfterTimer(string $name, float $time, callable $callback, array $params = []): int
    {
        self::checkTimer($name);
        $channel = new Channel(1);
        $tid = rgo(function () use ($name, $channel, $callback, $time, $params) {
            if ($ret = $channel->pop($time / 1000)) {
                return;
            }
            rgo(function () use ($name, $callback, $params) {
                try {
                    call_user_func($callback, ...$params);
                } catch (\Throwable $exception) {
                    throw $exception;
                } finally {
                    self::clearTimerByName($name);
                }
            });
        });
        self::$timers[$name] = ['name' => $name, 'chan' => $channel, 'tid' => $tid, 'type' => self::TYPE_AFTER, 'count' => 0];
        return $tid;
    }

    /**
     * @param string $name
     * @param float $time
     * @param callable $callback
     * @param array $params
     * @return int
     */
    public static function addTickTimer(string $name, float $time, callable $callback, array $params = []): int
    {
        self::checkTimer($name);
        $channel = new Channel(1);
        $tid = goloop(function () use ($name, $channel, $callback, $time, $params) {
            if ($ret = $channel->pop($time / 1000)) {
                return;
            }
            rgo(function () use ($name, $callback, $params) {
                self::$timers[$name]['count']++;
                call_user_func($callback, ...$params);
            });
        });
        self::$timers[$name] = ['name' => $name, 'chan' => $channel, 'tid' => $tid, 'type' => self::TYPE_TICKET, 'count' => 0];
        return $tid;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function clearTimerByName(string $name): bool
    {
        if (!isset(self::$timers[$name])) {
            return true;
        }
        self::$timers[$name]['chan']->push(true);
        unset(self::$timers[$name]);

        return true;
    }

    /**
     * @return bool
     */
    public static function clearTimers(): bool
    {
        foreach (self::$timers as $name => $timerData) {
            self::clearTimerByName($name);
        }
        return true;
    }
}
