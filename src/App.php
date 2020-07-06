<?php
declare(strict_types=1);

namespace Rabbit\Base;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use Rabbit\Base\Core\ObjectFactory;
use Swoole\Server;
use Throwable;

/**
 * Class App
 * @package rabbit
 */
class App
{
    /**
     * @var array
     */
    private static array $aliases = ['@rabbit' => __DIR__ . '/..'];
    /**
     * @var LoggerInterface
     */
    private static LoggerInterface $_logger;

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
     * @return LoggerInterface
     * @throws Throwable
     */
    public static function getLogger(): LoggerInterface
    {
        if (self::$_logger instanceof LoggerInterface) {
            return self::$_logger;
        }
        if (null === ObjectFactory::get('logger', false)) {
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
     * @param string $message
     * @param string|null $module
     * @throws Throwable
     */
    public static function debug(string $message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::DEBUG, $message, ['module' => $module ?? 'system']);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws Throwable
     */
    public static function emergency($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::EMERGENCY, $message, ['module' => $module ?? 'system']);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws Throwable
     */
    public static function alert($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::ALERT, $message, ['module' => $module ?? 'system']);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws Throwable
     */
    public static function critical($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::CRITICAL, $message, ['module' => $module ?? 'system']);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws Throwable
     */
    public static function error($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::ERROR, $message, ['module' => $module ?? 'system']);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws Throwable
     */
    public static function warning($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::WARNING, $message, ['module' => $module ?? 'system']);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws Throwable
     */
    public static function notice($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::NOTICE, $message, ['module' => $module ?? 'system']);
    }

    /**
     * @param $message
     * @param string|null $module
     * @throws Throwable
     */
    public static function info($message, string $module = null): void
    {
        static::getLogger()->log(LogLevel::INFO, $message, ['module' => $module ?? 'system']);
    }
}
