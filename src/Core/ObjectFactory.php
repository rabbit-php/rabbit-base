<?php
declare(strict_types=1);

namespace Rabbit\Base\Core;

use DI\Container;
use DI\ContainerBuilder;
use DI\Definition\Helper\DefinitionHelper;
use DI\DependencyException;
use DI\NotFoundException;
use InvalidArgumentException;
use Rabbit\Base\Contract\InitInterface;
use ReflectionClass;
use ReflectionException;
use Throwable;
use function DI\create;

/**
 * Class ObjectFactory
 * @package Rabbit\Base\Core
 */
class ObjectFactory
{
    /**
     * @var Container
     */
    private static ?Container $container = null;

    /**
     * @var array
     */
    private static array $definitions = [];
    /** @var array */
    private static array $initList = [];

    /**
     * @param array $definitions
     */
    public static function setDefinitions(array $definitions): void
    {
        self::$definitions['default'] = $definitions;
    }

    /**
     * @param array $init
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Throwable
     */
    public static function setPreInit(array $init): void
    {
        self::$definitions['pre'] = $init;
        self::getContainer();
        self::makeDefinitions(self::$definitions['pre']);
        foreach (self::$definitions['pre'] as $name => $definition) {
            self::$container->get($name);
        }
    }

    /**
     * @return array
     */
    public static function getDefinitions(): array
    {
        return self::$definitions;
    }

    /**
     * @throws DependencyException
     * @throws NotFoundException|ReflectionException
     * @throws Throwable
     */
    public static function init(): void
    {
        self::getContainer();
        isset(self::$definitions['default']) && self::makeDefinitions(self::$definitions['default']);
    }

    /**
     * @return Container
     * @throws Throwable
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
     * @throws DependencyException
     * @throws NotFoundException|ReflectionException
     */
    private static function makeDefinitions(array $definitions = [], bool $refresh = true): array
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
     * @param null $default
     * @return mixed|null
     * @throws Throwable
     */
    public static function get(string $name, bool $throwException = true, $default = null)
    {
        try {
            $obj = self::$container->get($name);
            if ($obj instanceof InitInterface && !in_array($name, self::$initList)) {
                self::$initList[] = $name;
                $obj->init();
            }
            return $obj;
        } catch (Throwable $e) {
            if ($throwException && $default === null) {
                throw $e;
            }
            return $default;
        }
    }

    /**
     * @param array $definitions
     * @throws DependencyException
     * @throws ReflectionException|NotFoundException
     */
    public static function add(array $definitions = []):void
    {
        self::makeDefinitions($definitions);
    }

    /**
     * @param $type
     * @param array $params
     * @param bool $singleTon
     * @return mixed
     * @throws DependencyException
     * @throws NotFoundException|ReflectionException
     */
    public static function createObject($type, array $params = [], bool $singleTon = true)
    {
        if (is_string($type)) {
            return self::make($type, $params, $singleTon);
        } elseif (is_array($type)) {
            if (!isset($type['class'])) {
                throw new InvalidArgumentException('Object configuration must be an array containing a "class" element.');
            }
            $class = $type['class'];
            unset($type['class']);
            $params = array_merge($type, $params);
            return self::make($class, $params, $singleTon);
        } elseif ($type instanceof DefinitionHelper) {
            return static::$container->get($type->getDefinition('')->getName());
        } elseif (is_callable($type, true)) {
            return static::$container->call($type, $params);
        }

        throw new InvalidArgumentException('Unsupported configuration type: ' . gettype($type));
    }

    /**
     * @param string $class
     * @param array $params
     * @param bool $singleTon
     * @return mixed|string
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    private static function make(string $class, array $params = [], bool $singleTon = true)
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
     * @throws ReflectionException
     */
    public static function configure($object, iterable $config):void
    {
        static $conParams = [];
        $class = get_class($object);
        if (!isset($conParams[$class])) {
            $obj = (new ReflectionClass($class))->getConstructor();
            if ($obj !== null) {
                foreach ($obj->getParameters() as $parameter) {
                    $conParams[$class][] = $parameter->getName();
                }
            }
        }

        foreach ($config as $action => $arguments) {
            if (substr($action, -2) === '()' && $action !== 'init()') {
                // method call
                call_user_func_array([$object, substr($action, 0, -2)], $arguments);
            } else {
                // property
                (!isset($conParams[$class]) || !in_array($action, $conParams[$class])) && $object->$action = $arguments;
            }
        }
        if ($object instanceof InitInterface) {
            $object->init();
        }
    }
}
