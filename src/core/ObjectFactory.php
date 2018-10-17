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
use rabbit\helper\ArrayHelper;
use rabbit\helper\ComposerHelper;
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
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public static function init()
    {
        self::$container = (new ContainerBuilder())->build();
        self::makeDefinitions(self::$definitions);
    }

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
    public static function reload(): void
    {
        self::init();
    }

    /**
     * @param string $name
     * @param bool $throwException
     * @return mixed|null
     * @throws \Exception
     */
    public static function get(string $name, bool $throwException = true)
    {
        try {
            return self::$container->get($name);
        } catch (\Exception $e) {
            if ($throwException) {
                throw $e;
            }
            return null;
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
            $obj = static::$container->make($type, $params);
            if ($singleTon) {
                static::$container->set($type, $obj);
            }
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            $params = ArrayHelper::merge($type, $params);
            return $singleTon ? static::$container->get($class) : static::$container->make($class, $params);
        } elseif (is_callable($type, true)) {
            return static::$container->call($type, $params);
        } elseif (is_array($type)) {
            throw new \InvalidArgumentException('Object configuration must be an array containing a "class" element.');
        }

        throw new \InvalidArgumentException('Unsupported configuration type: ' . gettype($type));
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
                                ($definitions[$name])->property($property, self::$container->get($v));
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
}