<?php
declare(strict_types=1);

namespace Rabbit\Base\Core;

use Rabbit\Base\App;
use Rabbit\Base\Exception\InvalidArgumentException;
use Swoole\Event;
use Throwable;

/**
 * Class Loop
 * @package Rabbit\Base\Core
 */
class Loop
{
    /** @var array */
    private static array $loopList = [];
    /** @var array */
    private static array $running = [];
    /** @var array */
    private static array $runGroup = [];

    /**
     * @param string $group
     * @param array $params
     * @param bool $autoRun
     * @return string
     * @throws Throwable
     */
    public static function addEvent(string $group, array $params, bool $autoRun = true): string
    {
        if (count($params) !== 4) {
            throw new InvalidArgumentException("swoole event must have 4 params");
        }
        if (!is_resource($params[0])) {
            throw new InvalidArgumentException("swoole event the 1th param must be resource");
        }
        if ($params[1] && !is_callable($params[1])) {
            throw new InvalidArgumentException("swoole event the 2th param must be null or callable");
        }
        $id = uniqid();
        !empty($params[1]) && $params[1] = function () use ($params, $id) {
            rgo(function () use ($params, $id) {
                call_user_func($params[1], $id);
            });
        };
        if (!$params[1] && !$params[2]) {
            throw new InvalidArgumentException("swoole event both read and write callbacks are empty");
        }
        if ($params[2] && !is_callable($params[2])) {
            throw new InvalidArgumentException("swoole event the 3th param must be null or callable");
        }
        !empty($params[2]) && $params[2] = function () use ($params, $id) {
            rgo(function () use ($params, $id) {
                call_user_func($params[2], $id);
            });
        };
        if ($params[3] && !in_array($params[3], [SWOOLE_EVENT_READ, SWOOLE_EVENT_WRITE, SWOOLE_EVENT_READ | SWOOLE_EVENT_WRITE])) {
            throw new InvalidArgumentException("swoole event the 4th param must be SWOOLE_EVENT_READ or SWOOLE_EVENT_WRITE or (SWOOLE_EVENT_READ | SWOOLE_EVENT_WRITE)");
        }
        self::$loopList[$group]['event'][$id] = [$params, $autoRun];
        if (isset(self::$runGroup[$group]) && self::$runGroup[$group] && $autoRun) {
            self::runEvent($group, $id);
        }
        return $id;
    }

    /**
     * @param string $group
     * @param array $params
     * @param bool $autoRun
     * @return string
     * @throws Throwable
     */
    public static function addTimer(string $group, array $params, bool $autoRun = true): string
    {
        if (count($params) !== 2) {
            throw new InvalidArgumentException("swoole timer only support 2 params");
        }
        if (!is_int($params[0]) && !is_float($params[0])) {
            throw new InvalidArgumentException("swoole timer the 1th param must be int or float");
        }
        if (!is_callable($params[1])) {
            throw new InvalidArgumentException("swoole timer the 2th param must be callable");
        }
        $id = uniqid();
        self::$loopList[$group]['timer'][$id] = [$params, $autoRun];
        if (isset(self::$runGroup[$group]) && self::$runGroup[$group] && $autoRun) {
            self::runTimer($group, $id);
        }
        return $id;
    }

    /**
     * @param string $group
     * @param string|null $id
     * @throws Throwable
     */
    public static function runEvent(string $group, ?string $id = null): void
    {
        if ($id === null) {
            foreach (self::$loopList[$group]['event'] as $id => $event) {
                if (isset(self::$running[$group]['event'][$id])) {
                    App::warning("Event group <$group> $id already running!", 'Loop');
                    continue;
                }
                [$event, $autoRun] = $event;
                if ($autoRun && Event::add(...$event)) {
                    self::$running[$group]['event'][$id] = $event[0];
                }
            }
        } elseif (is_string($id) && isset(self::$loopList[$group]['event'][$id]) && !isset(self::$running[$group]['event'][$id])) {
            [$event] = self::$loopList[$group]['event'][$id];
            Event::add(...$event);
            self::$running[$group]['event'][$id] = $event[0];
        }
    }

    /**
     * @param string $group
     * @param string|null $id
     * @throws Throwable
     */
    public static function runTimer(string $group, ?string $id = null): void
    {
        if ($id === null) {
            foreach (self::$loopList[$group]['timer'] as $id => $timer) {
                if (isset(self::$running[$group]['timer'][$id])) {
                    App::warning("Timer group <$group> $id already running!", 'Loop');
                    continue;
                }
                [$timer, $autoRun] = $timer;
                $autoRun && (self::$running[$group]['timer'][$id] = Timer::addTickTimer($group . '.timer.' . $id, ...array_merge($timer, [[$id]])));
            }
        } elseif (isset(self::$loopList[$group]['timer'][$id]) && !isset(self::$running[$group]['timer'][$id])) {
            [$timer] = self::$loopList[$group]['timer'][$id];
            self::$running[$group]['timer'][$id] = Timer::addTickTimer($group . '.timer.' . $id, ...array_merge($timer, [[$id]]));
        }
    }

    /**
     * @param string $group
     * @param string|null $id
     * @param bool $close
     */
    public static function stopEvent(string $group, ?string $id = null, bool $close = false): void
    {
        if ($id === null) {
            foreach (self::$running[$group]['event'] as $id => $stream) {
                Event::del($stream);
                $close && is_resource($stream) && @fclose($stream);
            }
            unset(self::$running[$group]['event']);
            self::$running[$group]['event'] = [];
        } elseif (is_string($id) && isset(self::$running[$group]['event'][$id])) {
            $stream = self::$running[$group]['event'][$id];
            Event::del($stream);
            $close && is_resource($stream) && @fclose($stream);
            unset(self::$running[$group]['event'][$id]);
        }
    }

    /**
     * @param string $group
     * @param string|null $id
     */
    public static function stopTimer(string $group, ?string $id = null): void
    {
        if ($id === null) {
            foreach (self::$running[$group]['timer'] as $id => $timer) {
                Timer::clearTimerByName($group . '.timer.' . $id);
            }
            unset(self::$running[$group]['timer']);
            self::$running[$group]['timer'] = [];
        } else {
            Timer::clearTimerByName($group . '.timer.' . $id);
            unset(self::$running[$group]['timer'][$id]);
        }
    }

    /**
     * @param string $group
     * @param string|null $id
     */
    public static function removeEvent(string $group, ?string $id = null): void
    {
        self::stopEvent($group, $id, true);
        if ($id === null) {
            unset(self::$loopList[$group]['event']);
            self::$running[$group]['event'] = [];
        } else {
            unset(self::$loopList[$group]['event'][$id]);
        }
    }

    /**
     * @param string $group
     * @param string|null $id
     */
    public static function removeTimer(string $group, ?string $id = null): void
    {
        self::stopTimer($group, $id);
        if ($id === null) {
            unset(self::$loopList[$group]['timer']);
            self::$running[$group]['timer'] = [];
        } else {
            unset(self::$loopList[$group]['timer'][$id]);
        }
    }

    /**
     * @param string $group
     * @throws Throwable
     */
    public static function run(string $group): void
    {
        self::$runGroup[$group] = true;
        if (isset(self::$loopList[$group])) {
            isset(self::$loopList[$group]['event']) && self::runEvent($group);
            isset(self::$loopList[$group]['timer']) && self::runTimer($group);
        }
    }

    /**
     * @param string $group
     */
    public static function stop(string $group): void
    {
        if (isset(self::$runGroup[$group])) {
            isset(self::$running[$group]['event']) && self::stopEvent($group);
            isset(self::$running[$group]['timer']) && self::stopTimer($group);
            self::$runGroup[$group] && self::$runGroup[$group] = false;
        }
    }
}
