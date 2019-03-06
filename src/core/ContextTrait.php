<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/3
 * Time: 15:31
 */

namespace rabbit\core;

use rabbit\helper\CoroHelper;

/**
 * Trait ContextTrait
 * @package rabbit\core
 */
trait ContextTrait
{
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
        if (isset(\Co::getContext()[$name])) {
            if (is_array(\Co::getContext()[$name]) && isset(\Co::getContext()[$name]['class'])) {
                \Co::getContext()[$name] = ObjectFactory::createObject(\Co::getContext()[$name], [], false);
            }
            return \Co::getContext()[$name];
        }
        return null;
    }

    /**
     * @param string $name
     * @param $value
     */
    public static function set(string $name, $value): void
    {
        \Co::getContext()[$name] = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return isset(\Co::getContext()[$name]);
    }

    /**
     * @param string $name
     */
    public static function delete(string $name): void
    {
        unset(\Co::getContext()[$name]);
    }
}