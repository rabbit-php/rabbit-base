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
    public static function getAll(string $key = null): ?array
    {
        return $key !== null ? \Co::getContext()[$key] : \Co::getContext();
    }

    /**
     * @param array $config
     */
    public static function setAll($config = [], string $key = null): void
    {
        foreach ($config as $name => $value) {
            self::set($name, $value, $key);
        }
    }

    /**
     * @param string $name
     * @param $value
     */
    public static function set(string $name, $value, string $key = null): void
    {
        if ($key !== null) {
            \Co::getContext()[$key][$name] = $value;
        } else {
            \Co::getContext()[$name] = $value;
        }
    }

    /**
     * @param string $name
     * @param string|null $key
     * @return mixed
     */
    public static function get(string $name, string $key = null)
    {
        if ($key !== null) {
            if (!isset(\Co::getContext()[$key])) {
                return null;
            } else {
                return isset(\Co::getContext()[$key][$name]) ? \Co::getContext()[$key][$name] : null;
            }
        }
        return isset(\Co::getContext()[$name]) ? \Co::getContext()[$name] : null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function has(string $name, string $key = null): bool
    {
        if ($key !== null) {
            return isset(\Co::getContext()[$key]) && isset(\Co::getContext()[$key][$name]);
        }
        return isset(\Co::getContext()[$name]);
    }

    /**
     * @param string $name
     */
    public static function delete(string $name, string $key = null): void
    {
        if ($key !== null && isset(\Co::getContext()[$key]) && isset(\Co::getContext()[$key][$name])) {
            unset(\Co::getContext()[$key][$name]);
        } elseif (isset(\Co::getContext()[$name])) {
            unset(\Co::getContext()[$name]);
        }
    }
}
