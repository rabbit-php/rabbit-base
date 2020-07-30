<?php
declare(strict_types=1);

namespace Rabbit\Base\Core;

use Co\Channel;
use rabbit\App;
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
     * @param string $name
     * @param float $time
     * @param callable $callback
     * @param array $params
     * @return int
     * @throws Exception
     */
    public static function addAfterTimer(string $name, float $time, callable $callback, array $params = []): int
    {
        self::checkTimer($name);
        $channel = new Channel(1);
        $tid = rgo(function () use ($name, $channel, $callback, $time, $params) {
            if ($channel->pop($time / 1000)) {
                return;
            }
            rgo(function () use ($name, $callback, $params) {
                try {
                    self::$timers[$name]['count']++;
                    call_user_func($callback, ...$params);
                } catch (Throwable $exception) {
                    App::error($exception->getMessage());
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
     * @throws Exception
     */
    public static function addTickTimer(string $name, float $time, callable $callback, array $params = []): int
    {
        self::checkTimer($name);
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
                    print_r(ExceptionHelper::convertExceptionToArray($throwable));
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
