<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/30
 * Time: 13:47
 */

namespace rabbit\core;

use DI\Container;
use DI\ContainerBuilder;
use DI\Definition\Helper\DefinitionHelper;
use function DI\create;

/**
 * Class ObjectFactory
 * @package rabbit\core
 */
class ObjectFactory
{
    /**
     * @var Container
     */
    private static $container;

    /**
     * @var array
     */
    private static $definitions = [];

    /**
     * @param array $definitions
     */
    public static function setDefinitions(array $definitions): void
    {
        self::$definitions = $definitions;
    }

    /**
     * @return array
     */
    public static function getDefiinitions(): array
    {
        return self::$definitions;
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public static function init(bool $auto = true)
    {
        self::getContainer();
        self::makeDefinitions(self::$definitions);
        if ($auto) {
            foreach (self::$definitions as $name => $definition) {
                self::$container->get($name);
            }
        }
    }

    /**
     * @return Container
     */
    public static function getContainer(): Container
    {
        if (self::$container) {
            return self::$container;
        }
        self::$container = (new ContainerBuilder())->build();
        return self::$container;
    }

    /**
     * @param array $definitions
     * @param bool $refresh
     * @return array
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    private static function makeDefinitions(array $definitions = [], bool $refresh = true)
    {
        foreach ($definitions as $name => $value) {
            if (is_array($value) && isset($value['class'])) {
                $class = $value['class'];
                unset($value['class']);
                $definitions[$name] = create($class);
                foreach ($value as $property => $v) {
                    $auto = true;
                    if (is_array($v) && isset($v['auto'])) {
                        $auto = $v['auto'];
                        unset($v['auto']);
                    }
                    if (is_array($v) && isset($v['class'])) {
                        if ($auto) {
                            $define = self::makeDefinitions([$property => $v], false);
                            ($definitions[$name])->property($property, $define[$property]);
                        } else {
                            ($definitions[$name])->property($property, $v);
                        }
                    } elseif (is_array($v)) {
                        foreach ($v as $index => $def) {
                            if ($def instanceof DefinitionHelper) {
                                $v[$index] = $def->getDefinition('');
                            } elseif (is_string($v) && strpos($v, '\\') !== false) {
                                $v[$index] = self::$container->get($v);
                            }
                        }
                        ($definitions[$name])->property($property, $v);
                    } elseif ($v instanceof DefinitionHelper) {
                        ($definitions[$name])->property($property, $v->getDefinition(''));
                    } elseif (is_string($v) && strpos($v, '\\') !== false) {
                        ($definitions[$name])->property($property, self::$container->get($v));
                    } else {
                        ($definitions[$name])->property($property, $v);
                    }
                }
            }
            if ($refresh) {
                self::$container->set($name, $definitions[$name]);
            }
        }
        return $definitions;
    }

    /**
     * @param string $name
     * @param bool $throwException
     * @return mixed|null
     * @throws \Exception
     */
    public static function get(string $name, bool $throwException = true, $default = null)
    {
        try {
            return self::$container->get($name);
        } catch (\Throwable $e) {
            if ($throwException && $default === null) {
                throw $e;
            }
            return $default;
        }
    }

    /**
     * @param array $definitions
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public static function set(array $definitions = [])
    {
        self::makeDefinitions($definitions);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function has(string $name): bool
    {
        return self::$container->has($name);
    }

    /**
     * @param string $name
     * @return bool
     */
    public static function hasDef(string $name): bool
    {
        return isset(self::$definitions[$name]);
    }

    /**
     * @param $type
     * @param array $params
     * @param bool $singleTon
     * @return mixed
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public static function createObject($type, array $params = [], bool $singleTon = true)
    {
        if (is_string($type)) {
            return self::make($type, $params, $singleTon);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            $params = array_merge($type, $params);
            return self::make($class, $params, $singleTon);
        } elseif ($type instanceof DefinitionHelper) {
            return static::$container->get($type->getDefinition('')->getName());
        } elseif (is_callable($type, true)) {
            return static::$container->call($type, $params);
        } elseif (is_array($type)) {
            throw new \InvalidArgumentException('Object configuration must be an array containing a "class" element.');
        }

        throw new \InvalidArgumentException('Unsupported configuration type: ' . gettype($type));
    }

    /**
     * @param string $class
     * @param array $params
     * @param bool $singleTon
     * @return mixed
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    private static function make(string $class, array $params = [], bool $singleTon)
    {
        if ($singleTon) {
            if (in_array($class, static::$container->getKnownEntryNames())) {
                return static::$container->get($class);
            }
            $obj = static::$container->make($class, $params);
            static::$container->set($class, $obj);
        } else {
            $obj = static::$container->make($class, $params);
        }
        self::configure($obj, $params);
        return $obj;
    }

    /**
     * @param $object
     * @param iterable $config
     * @return mixed
     */
    public static function configure($object, iterable $config)
    {
        foreach ($config as $action => $arguments) {
            if (substr($action, -2) === '()') {
                // method call
                call_user_func_array([$object, substr($action, 0, -2)], $arguments);
            } else {
                // property
                $object->$action = $arguments;
            }
        }
    }
}