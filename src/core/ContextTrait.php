<?php


namespace rabbit\core;

/**
 * Trait ContextTrait
 * @package rabbit\core
 */
trait ContextTrait
{
    /** @var self */
    private static $instance;

    /**
     * @param $name
     * @param $arguments
     */
    public static function __callStatic($name, $arguments)
    {
        $name .= 'Context';
        if (!self::$instance) {
            self::$instance = new static();
        }
        return self::$instance->$name(...$arguments);
    }
}