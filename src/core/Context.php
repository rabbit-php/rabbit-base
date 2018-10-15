<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9
 * Time: 13:36
 */

namespace rabbit\core;


use rabbit\helper\CoroHelper;

class Context
{
    private static $context = [];

    public static function getAll(): ?array
    {
        return self::$context[CoroHelper::getId()];
    }

    public static function setAll($config = [])
    {
        foreach ($config as $name => $value) {
            self::set($name, $value);
        }
    }

    public static function get(string $name)
    {
        $id = CoroHelper::getId();
        if (isset(self::$context[$id][$name])) {
            if (is_array(self::$context[$id][$name]) && isset(self::$context[$id][$name]['class'])) {
                self::$context[$id][$name] = ObjectFactory::createObject(self::$context[$id][$name], [], false);
            }
            return self::$context[$id][$name];
        }
        return null;
    }

    public static function set(string $name, $value)
    {
        self::$context[CoroHelper::getId()][$name] = $value;
    }

    public static function has($name): bool
    {
        return isset(self::$context[CoroHelper::getId()][$name]) || isset(self::$context[0][$name]);
    }

    public static function release()
    {
        unset(self::$context[CoroHelper::getId()]);
    }
}