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
    /**
     * @return array|null
     */
    public static function getAll(string $key = 'system'): ?array
    {
        return \Co::getContext();
    }

    /**
     * @param array $config
     */
    public static function setAll($config = [], string $key = 'system'): void
    {
        foreach ($config as $name => $value) {
            self::set($name, $value, $key);
        }
    }

    /**
     * @param string $name
     * @return null
     */
    public static function get(string $name, string $key = 'system')
    {
        $context = \Co::getContext();
        if (isset($context[$key][$name])) {
            if (is_array($context[$key][$name]) && isset($context[$key][$name]['class'])) {
                $context[$key][$name] = ObjectFactory::createObject($context[$key][$name], [], false);
            }
            return $context[$key][$name];
        }
        return null;
    }

    /**
     * @param string $name
     * @param $value
     */
    public static function set(string $name, $value, string $key = 'system'): void
    {
        \Co::getContext()[$key][$name] = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function has(string $name, string $key = 'system'): bool
    {
        return isset(\Co::getContext()[$key][$name]);
    }

    /**
     * @param string $name
     */
    public static function delete(string $name, string $key = 'system'): void
    {
        unset(\Co::getContext()[$key][$name]);
    }
}