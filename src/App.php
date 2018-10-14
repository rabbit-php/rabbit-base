<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/30
 * Time: 10:30
 */

namespace rabbit\framework;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use rabbit\framework\core\ObjectFactory;
use swoole_server;

class App
{
    private static $aliases = ['@rabbit' => __DIR__ . '/..'];

    /**
     * @var swoole_server
     */
    private static $_server;

    private static $_logger;

    public static function getLogger(): LoggerInterface
    {
        if (self::$_logger instanceof LoggerInterface) {
            return self::$_logger;
        }
        if ((self::$_logger = ObjectFactory::get('logger', false)) === null) {
            self::$_logger = ObjectFactory::get(NullLogger::class);
        }
        return self::$_logger;
    }

    public static function setLogger(LoggerInterface $logger)
    {
        self::$_logger = $logger;
    }

    public static function setServer(\Swoole\Server $server)
    {
        self::$_server = $server;
    }

    public static function getServer(): ?\Swoole\Server
    {
        return self::$_server;
    }

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

    public static function setAlias($alias, $path)
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
}