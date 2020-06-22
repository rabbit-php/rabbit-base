<?php

namespace rabbit\core;

use Co\System;
use rabbit\contract\AbstractTimer;

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
    public static function addAfterTimer(string $name, float $time, callable $callback, array $params = []): int
    {
        self::checkTimer($name);
        self::$timers[$name] = ['name' => $name, 'type' => self::TYPE_AFTER];
        $tid = rgo(function () use ($name, $time, $callback, $params) {
            System::sleep($time / 1000);
            if (isset(self::$timers[$name])) {
                try {
                    call_user_func($callback, ...$params);
                } catch (\Throwable $exception) {
                    throw $exception;
                } finally {
                    self::clearTimerByName($name);
                }
            }
        });
        self::$timers[$name]['tid'] = $tid;
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
        self::$timers[$name] = ['name' => $name, 'type' => self::TYPE_AFTER];
        $tid = rgo(function () use ($name, $callback, $time, $params) {
            while (isset(self::$timers[$name])) {
                call_user_func($callback, ...$params);
                System::sleep($time / 1000);
            }
        });
        self::$timers[$name]['tid'] = $tid;
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
        unset(self::$timers[$name]);
        return true;
    }

    /**
     * @return bool
     */
    public static function clearTimers(): bool
    {
        self::$timers = [];
        return true;
    }
}
