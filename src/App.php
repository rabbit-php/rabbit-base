<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/30
 * Time: 10:30
 */

namespace rabbit;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use rabbit\core\ObjectFactory;
use swoole_server;

/**
 * Class App
 * @package rabbit
 */
class App
{
    /**
     * @var array
     */
    private static $aliases = ['@rabbit' => __DIR__ . '/..'];

    /**
     * @var swoole_server
     */
    private static $_server;

    /**
     * @var LoggerInterface
     */
    private static $_logger;

    /**
     * @return LoggerInterface
     * @throws \Exception
     */
    public static function getLogger(): LoggerInterface
    {
        if (self::$_logger instanceof LoggerInterface) {
            return self::$_logger;
        }
        if ((self::$_logger = ObjectFactory::get('logger', null, false)) === null) {
            self::$_logger = ObjectFactory::get(NullLogger::class);
        }
        return self::$_logger;
    }

    /**
     * @param LoggerInterface $logger
     */
    public static function setLogger(LoggerInterface $logger): void
    {
        self::$_logger = $logger;
    }

    /**
     * @param \Swoole\Server $server
     */
    public static function setServer(\Swoole\Server $server): void
    {
        self::$_server = $server;
    }

    /**
     * @return null|\Swoole\Server
     */
    public static function getServer(): \Swoole\Server
    {
        if (self::$_server) {
            return self::$_server;
        }
        return new \Swoole\Server('0.0.0.0');
    }

    /**
     * @param $alias
     * @param bool $throwException
     * @return null|string
     */
    public static function getAlias($alias, $throwException = true): ?string
    {
        if (strncmp($alias, '@', 1)) {
            // not an alias
            return $alias;
        }

        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);

        if (isset(static::$aliases[$root])) {
            if (is_string(static::$aliases[$root])) {
                return $pos === false ? static::$aliases[$root] : static::$aliases[$root] . substr($alias, $pos);
            }

            foreach (static::$aliases[$root] as $name => $path) {
                if (strpos($alias . '/', $name . '/') === 0) {
                    return $path . substr($alias, strlen($name));
                }
            }
        }

        if ($throwException) {
            throw new \InvalidArgumentException("Invalid path alias: $alias");
        }

        return null;
    }

    /**
     * @param $alias
     * @param $path
     */
    public static function setAlias($alias, $path): void
    {
        if (strncmp($alias, '@', 1)) {
            $alias = '@' . $alias;
        }
        $pos = strpos($alias, '/');
        $root = $pos === false ? $alias : substr($alias, 0, $pos);
        if ($path !== null) {
            $path = strncmp($path, '@', 1) ? rtrim($path, '\\/') : static::getAlias($path);
            if (!isset(static::$aliases[$root])) {
                if ($pos === false) {
                    static::$aliases[$root] = $path;
                } else {
                    static::$aliases[$root] = [$alias => $path];
                }
            } elseif (is_string(static::$aliases[$root])) {
                if ($pos === false) {
                    static::$aliases[$root] = $path;
                } else {
                    static::$aliases[$root] = [
                        $alias => $path,
                        $root => static::$aliases[$root],
                    ];
                }
            } else {
                static::$aliases[$root][$alias] = $path;
                krsort(static::$aliases[$root]);
            }
        } elseif (isset(static::$aliases[$root])) {
            if (is_array(static::$aliases[$root])) {
                unset(static::$aliases[$root][$alias]);
            } elseif ($pos === false) {
                unset(static::$aliases[$root]);
            }
        }
    }

    /**
     * @param string $log
     * @param string|null $module
     * @throws \Exception
     */
    public function debug(string $log, string $module = null): void
    {
        static::getLogger()->log(LogLevel::DEBUG, $log, ['module' => $module ?? ObjectFactory::get('appName')]);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws \Exception
     */
    public function emergency($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::EMERGENCY, $log, ['module' => $module ?? ObjectFactory::get('appName')]);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws \Exception
     */
    public function alert($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::ALERT, $log, ['module' => $module ?? ObjectFactory::get('appName')]);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws \Exception
     */
    public function critical($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::CRITICAL, $log, ['module' => $module ?? ObjectFactory::get('appName')]);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws \Exception
     */
    public function error($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::ERROR, $log, ['module' => $module ?? ObjectFactory::get('appName')]);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws \Exception
     */
    public function warning($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::WARNING, $log, ['module' => $module ?? ObjectFactory::get('appName')]);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws \Exception
     */
    public function notice($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::NOTICE, $log, ['module' => $module ?? ObjectFactory::get('appName')]);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws \Exception
     */
    public function info($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::INFO, $log, ['module' => $module ?? ObjectFactory::get('appName')]);
    }

}