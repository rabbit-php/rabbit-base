<?php


namespace rabbit\core;

use rabbit\App;
use rabbit\exception\InvalidArgumentException;

/**
 * Class Loop
 * @package rabbit\core
 */
class Loop
{
    /** @var array */
    private static $loopList = [];
    /** @var array */
    private static $running = [];
    /** @var array */
    private static $runGroup = [];

    /**
     * @param string $group
     * @param array $params
     */
    public static function addEvent(string $group, array $params): void
    {
        if (count($params) !== 4) {
            throw new InvalidArgumentException("swoole event must have 4 param");
        }
        if (!is_resource($params[0])) {
            throw new InvalidArgumentException("swoole event the 1th param must be resource");
        }
        if ($params[1] && !is_callable($params[1])) {
            throw new InvalidArgumentException("swoole event the 2th param must be null or callable");
        }
        if ($params[2] && !is_callable($params[2])) {
            throw new InvalidArgumentException("swoole event the 3th param must be null or callable");
        }
        if ($params[3] && !is_int($params[3])) {
            throw new InvalidArgumentException("swoole event the 4th param must be SWOOLE_EVENT_READ or SWOOLE_EVENT_WRITE or SWOOLE_EVENT_READ | SWOOLE_EVENT_WRITE)");
        }
        $id = (int)$params[0];
        self::$loopList[$group]['event'][$id] = $params;
        if (isset(self::$runGroup[$group]) && self::$runGroup[$group]) {
            self::runEvent($group, $id);
        }
    }

    /**
     * @param string $group
     * @param array $params
     * @return string
     */
    public static function addTimer(string $group, array $params): string
    {
        if (count($params) < 2) {
            throw new InvalidArgumentException("swoole timer at least have 2 param");
        }
        if (!is_int($params[0]) && !is_float($params[0])) {
            throw new InvalidArgumentException("swoole timer the 1th param must be int or float");
        }
        if (!is_callable($params[1])) {
            throw new InvalidArgumentException("swoole timer the 2th param must be callable");
        }
        if (isset($params[2]) && !is_array($params[2])) {
            throw new InvalidArgumentException("swoole timer the 3th param must be array");
        }
        $id = uniqid();
        self::$loopList[$group]['timer'][$id] = $params;
        if (isset(self::$runGroup[$group]) && self::$runGroup[$group]) {
            self::runTimer($group, $id);
        }
        return $id;
    }

    /**
     * @param string $group
     * @param string|null $id
     */
    public static function runEvent(string $group, $id = null)
    {
        if ($id === null) {
            foreach (self::$loopList[$group]['event'] as $id => $event) {
                if (isset(self::$running[$group]['evnet'][$id])) {
                    App::warning("Event group <$group> $id already running!", 'Loop');
                    continue;
                }
                swoole_event_add(...$event);
                self::$running[$group]['evnet'][$id] = $event[0];
            }
        } elseif (is_int($id) && isset(self::$loopList[$group]['event'][$id])) {
            swoole_event_add(...self::$loopList[$group]['event'][$id]);
            self::$running[$group]['evnet'][$id] = self::$loopList[$group]['event'][$id][0];
        }
    }

    /**
     * @param string $group
     * @param string|null $id
     */
    public static function runTimer(string $group, ?string $id = null)
    {
        if ($id === null) {
            foreach (self::$loopList[$group]['timer'] as $id => $timer) {
                if (isset(self::$running[$group]['timer'][$id])) {
                    App::warning("Timer group <$group> $id already running!", 'Loop');
                    continue;
                }
                self::$running[$group]['timer'][$id] = \Swoole\Timer::tick(...$timer);
            }
        } elseif (is_string($id) && isset(self::$loopList[$group]['timer'][$id])) {
            self::$running[$group]['timer'][$id] = \Swoole\Timer::tick(...self::$loopList[$group]['timer'][$id]);
        }
    }

    /**
     * @param string $group
     * @param int|null $id
     */
    public static function stopEvent(string $group, ?int $id = null): void
    {
        if ($id === null) {
            foreach (self::$running[$group]['event'] as $id => $stream) {
                swoole_event_del($stream);
                is_resource($stream) && @fclose($stream);
                unset(self::$running[$group]['event'][$id]);
            }
        } elseif (is_int($id) && isset(self::$running[$group]['event'][$id])) {
            $stream = self::$running[$group]['event'][$id];
            swoole_event_del($stream);
            is_resource($stream) && @fclose($stream);
            unset(self::$running[$group]['event'][$id]);
        }
    }

    /**
     * @param string $group
     * @param string|null $id
     */
    public static function stopTimer(string $group, ?string $id): void
    {
        if ($id === null) {
            foreach (self::$running[$group]['timer'] as $id => $timer) {
                \Swoole\Timer::clear($timer);
                unset(self::$running[$group]['timer'][$id]);
            }
        } else {
            \Swoole\Timer::clear(self::$running[$group]['timer'][$id]);
            unset(self::$running[$group]['timer'][$id]);
        }
    }

    /**
     * @param string $group
     */
    public static function run(string $group)
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
    public static function stop(string $group)
    {
        if (isset(self::$runGroup[$group])) {
            isset(self::$running[$group]['event']) && self::stopEvent($group);
            isset(self::$running[$group]['timer']) && self::stopTimer($group);
            self::$runGroup[$group] && self::$runGroup[$group] = false;
        }
    }
}