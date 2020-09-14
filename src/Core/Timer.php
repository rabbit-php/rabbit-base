<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Co\Channel;
use Rabbit\Base\App;
use Rabbit\Base\Helper\ExceptionHelper;
use Throwable;

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
     * @param string $name
     * @return bool
     * @throws Exception
     */
    public static function stopTimer(string $name): bool
    {
        self::checkTimer($name);
        $timer = self::getTimer($name);
        $timer['chan']->push(true);
        return true;
    }

    /**
     * @author Albert <63851587@qq.com>
     * @param float $time
     * @param callable $callback
     * @param string $name
     * @param array $params
     * @return integer
     */
    public static function addAfterTimer(float $time, callable $callback, string $name = null, array $params = []): int
    {
        if ($name === null) {
            $name = uniqid();
        } else {
            self::checkTimer($name);
        }
        $channel = new Channel(1);
        $tid = go(function () use ($name, $channel, $callback, $time, $params) {
            if ($channel->pop($time / 1000)) {
                return;
            }
            go(function () use ($name, $callback, $params) {
                try {
                    self::clearTimerByName($name);
                    call_user_func($callback, ...$params);
                } catch (Throwable $exception) {
                    App::error($exception->getMessage());
                    throw $exception;
                }
            });
        });
        self::$timers[$name] = ['name' => $name, 'chan' => $channel, 'tid' => $tid, 'type' => self::TYPE_AFTER, 'count' => 0];
        return $tid;
    }

    /**
     * @author Albert <63851587@qq.com>
     * @param float $time
     * @param callable $callback
     * @param string $name
     * @param array $params
     * @return integer
     */
    public static function addTickTimer(float $time, callable $callback, string $name = null, array $params = []): int
    {
        if ($name === null) {
            $name = uniqid();
        } else {
            self::checkTimer($name);
        }
        $channel = new Channel(1);
        $tid = go(function () use ($name, $channel, $callback, $time, $params) {
            while (true) {
                try {
                    if ($channel->pop($time / 1000)) {
                        return;
                    }
                    rgo(function () use ($name, $callback, $params) {
                        self::$timers[$name]['count']++;
                        call_user_func($callback, ...$params);
                    });
                } catch (\Throwable $throwable) {
                    App::error(ExceptionHelper::dumpExceptionToString($throwable));
                }
            }
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
