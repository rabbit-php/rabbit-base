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
        return self::$context[CoroHelper::getId()];
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
        $id = CoroHelper::getId();
        if (isset(self::$context[$id][$name])) {
            if (is_array(self::$context[$id][$name]) && isset(self::$context[$id][$name]['class'])) {
                self::$context[$id][$name] = ObjectFactory::createObject(self::$context[$id][$name], [], false);
            }
            return self::$context[$id][$name];
        }
        return null;
    }

    /**
     * @param string $name
     * @param $value
     */
    public static function set(string $name, $value): void
    {
        self::$context[CoroHelper::getId()][$name] = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return isset(self::$context[CoroHelper::getId()][$name]);
    }

    /**
     * @param string $name
     */
    public static function delete(string $name): void
    {
        unset(self::$context[CoroHelper::getId()][$name]);
    }

    /**
     *
     */
    public static function release(): void
    {
        unset(self::$context[CoroHelper::getId()]);
    }
}