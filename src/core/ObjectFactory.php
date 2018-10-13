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
use function DI\create;
use rabbit\framework\helper\ComposerHelper;

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

    public static function get(string $name)
    {
        return self::$container->get($name);
    }

    public static function set(array $definitions = [])
    {
        self::makeDefinitions($definitions);
    }

    public static function createObject($type, array $params = [], bool $singleTon = true)
    {
        if (is_string($type)) {
            return $singleTon ? static::$container->get($type) : static::$container->make($type, $params);
        } elseif (is_array($type) && isset($type['class'])) {
            $class = $type['class'];
            unset($type['class']);
            return $singleTon ? static::$container->get($class) : static::$container->make($class, $params);
        } elseif (is_callable($type, true)) {
            return static::$container->call($type, $params);
        } elseif (is_array($type)) {
            throw new \InvalidArgumentException('Object configuration must be an array containing a "class" element.');
        }

        throw new \InvalidArgumentException('Unsupported configuration type: ' . gettype($type));
    }

    private static function makeDefinitions(array $definitions = [])
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
                    if (is_array($v) && isset($v['class']) && $auto) {
                        self::makeDefinitions($v);
                        ($definitions[$name])->property($property, self::$container->get($v['class']));
                    } else {
                        ($definitions[$name])->property($property, $v);
                    }
                }
            }
            self::$container->set($name, $definitions[$name]);
        }
    }
}