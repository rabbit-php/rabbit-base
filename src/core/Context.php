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
 * @method get(string $name)
 * @method void set(string $name)
 * @method ?\Co\Context getAll()
 * @method void setAll(array $config = [])
 * @method bool has(string $name)
 * @method void delete(string $name)
 * @package rabbit\core
 */
class Context
{
    /** @var self */
    private static $instance;

    protected $context;

    public function __construct()
    {
        $this->context = new \Co\Context();
    }

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

    /**
     * @return array|null
     */
    public function getAllContext(): ?\Co\Context
    {
        return $this->context;
    }

    /**
     * @param array $config
     */
    public function setAllContext(array $config = []): void
    {
        foreach ($config as $name => $value) {
            $this->setContext($name, $value, $key);
        }
    }

    /**
     * @param string $name
     * @return null
     */
    public function getContext(string $name)
    {
        if (isset($this->context[$name])) {
            if (is_array($this->context[$name]) && isset($this->context[$name]['class'])) {
                $this->context[$name] = ObjectFactory::createObject($this->context[$name], [], false);
            }
            return $this->context[$name];
        }
        return null;
    }

    /**
     * @param string $name
     * @param $value
     */
    public function setContext(string $name, $value): void
    {
        $this->context[$name] = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasContext(string $name): bool
    {
        return isset($this->context[$name]);
    }

    /**
     * @param string $name
     */
    public function deleteContext(string $name): void
    {
        unset($this->context[$name]);
    }
}