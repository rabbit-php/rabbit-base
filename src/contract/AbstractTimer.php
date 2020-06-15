<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/28
 * Time: 10:24
 */

namespace rabbit\contract;

use rabbit\core\Exception;

/**
 * Class TimerInterface
 * @package rabbit\contract
 */
abstract class AbstractTimer
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
    protected static $timers = [];

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
     * @param float $time
     * @param callable $callback
     * @param array $params
     * @return int
     */
    abstract public static function addAfterTimer(string $name, float $time, callable $callback, array $params = []): int;

    /**
     * @param string $name
     * @param float $time
     * @param callable $callback
     * @param array $params
     * @return int
     */
    abstract public static function addTickTimer(string $name, float $time, callable $callback, array $params = []): int;

    /**
     * @param string $name
     * @return bool
     */
    abstract public static function clearTimerByName(string $name): bool;

    /**
     * @return bool
     */
    abstract public static function clearTimers(): bool;
}
