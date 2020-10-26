<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Rabbit\Base\App;
use Rabbit\Base\Helper\ExceptionHelper;

/**
 * Class Timer
 * @package Rabbit\Base\Core
 */
class Timer
{
    const TYPE_AFTER = 'after';
    const TYPE_TICKET = 'tick';

    /**
     * @var array 所有定时器
     */
    protected static array $timers = [];

    /**
     * @return array
     */
    public static function getTimers(): array
    {
        return self::$timers;
    }

    /**
     * @param string $name
     * @param null $default
     * @return array
     */
    public static function getTimer(string $name, $default = null): array
    {
        return isset(self::$timers[$name]) ? self::$timers[$name] : $default;
    }

    /**
     * @param string $name
     * @return bool
     * @throws Exception
     */
    public static function checkTimer(string $name): bool
    {
        if (isset(self::$timers[$name])) {
            throw new Exception("$name timer already exists");
        }
        return true;
    }

    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-10-25
     * @param float $time
     * @param callable $callback
     * @param string $name
     * @param array $params
     * @return void
     */
    public static function addAfterTimer(float $time, callable $callback, string $name = null, array $params = [])
    {
        if ($name === null) {
            $name = uniqid();
        } else {
            self::checkTimer($name);
        }
        self::$timers[$name] = ['name' => $name, 'type' => self::TYPE_AFTER, 'count' => 0];
        return rgo(function () use ($name, $time, $callback, $params) {
            usleep($time);
            rgo(function () use ($name, $callback, $params) {
                self::clearTimerByName($name);
                call_user_func($callback, ...$params);
            });
        });
    }

    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-10-25
     * @param float $time
     * @param callable $callback
     * @param string $name
     * @param array $params
     * @return void
     */
    public static function addTickTimer(float $time, callable $callback, string $name = null, array $params = [])
    {
        if ($name === null) {
            $name = uniqid();
        } else {
            self::checkTimer($name);
        }
        self::$timers[$name] = ['name' => $name, 'type' => self::TYPE_TICKET, 'count' => 0];
        return rgo(function () use ($name, $callback, $time, $params) {
            while (isset(self::$timers[$name])) {
                usleep($time);
                try {
                    rgo(function () use ($name, $callback, $params) {
                        self::$timers[$name]['count']++;
                        call_user_func($callback, ...$params);
                    });
                } catch (\Throwable $throwable) {
                    App::error(ExceptionHelper::dumpExceptionToString($throwable));
                }
            }
        });
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
        foreach (self::$timers as $name => $timerData) {
            self::clearTimerByName($name);
        }
        return true;
    }
}
