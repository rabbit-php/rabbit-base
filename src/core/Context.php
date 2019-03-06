<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9
 * Time: 13:36
 */

namespace rabbit\core;


/**
 * Class Context
 * @package rabbit\core
 */
class Context
{
    protected static $key = 'system';

    /**
     * @return array|null
     */
    public static function getAll(): ?array
    {
        return \Co::getContext();
    }

    /**
     * @param array $config
     */
    public static function setAll($config = []): void
    {
        foreach ($config as $name => $value) {
            self::set($name, $value);
        }
    }

    /**
     * @param string $name
     * @return null
     */
    public static function get(string $name)
    {
        $context = \Co::getContext();
        if (isset($context[self::$key][$name])) {
            if (is_array($context[self::$key][$name]) && isset($context[self::$key][$name]['class'])) {
                $context[self::$key][$name] = ObjectFactory::createObject($context[self::$key][$name], [], false);
            }
            return $context[self::$key][$name];
        }
        return null;
    }

    /**
     * @param string $name
     * @param $value
     */
    public static function set(string $name, $value): void
    {
        \Co::getContext()[self::$key][$name] = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return isset(\Co::getContext()[self::$key][$name]);
    }

    /**
     * @param string $name
     */
    public static function delete(string $name): void
    {
        unset(\Co::getContext()[self::$key][$name]);
    }
}