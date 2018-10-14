<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/30
 * Time: 13:47
 */

namespace rabbit\framework\core;

use DI\Container;
use DI\ContainerBuilder;
use DI\Definition\Helper\DefinitionHelper;
use rabbit\framework\helper\ArrayHelper;
use rabbit\framework\helper\ComposerHelper;
use function DI\create;

class ObjectFactory
{
    /**
     * @var Container
     */
    private static $container;

    /**
     * @var
     */
    public static $definitions;

    public static function init()
    {
        self::$container = (new ContainerBuilder())->build();
        self::makeDefinitions(self::$definitions);
    }

    public static function reload(): void
    {
        self::init();
        ComposerHelper::getLoader();
    }

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

    public static function set(array $definitions = [])
    {
        self::makeDefinitions($definitions);
    }

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
                    } elseif ($v instanceof DefinitionHelper) {
                        ($definitions[$name])->property($property, $v->getDefinition($v->className));
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