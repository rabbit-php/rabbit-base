<?php

declare(strict_types=1);

namespace Rabbit\Base\DI;

use InvalidArgumentException;
use Rabbit\Base\Contract\InitInterface;
use Rabbit\Base\Helper\ArrayHelper;
use ReflectionClass;
use ReflectionParameter;
use Throwable;

class ObjectFactory
{
    private Container $container;

    private Container $definition;

    private array $initList = [];

    private array $args = [];

    private array $props = [];

    private array $lazy = [];

    public function __construct(
        public array $config = [],
        private string $classKey = '{}',
        private string $funcKey = '()'
    ) {
        $this->container = new Container();
        $this->definition = new Container();
    }

    public function pre(array $pre): self
    {
        $this->lazy = $pre;
        $this->build($pre);
        foreach (array_keys($pre) as $name) {
            $this->get($name);
        }
        return $this;
    }

    public function lazy(array $lazy): self
    {
        foreach ($lazy as $name => $val) {
            if ((is_array($val) && ($val[$this->classKey] ?? false)) || $val instanceof ArrayDefinition || $val instanceof Definition || is_object($val)) {
                $this->lazy[$name] = $val;
            } else {
                $this->config[$name] = $val;
            }
        }
        return $this;
    }

    private function build(array $definitions = [], bool $refresh = true): void
    {
        foreach ($definitions as $name => $value) {
            if (is_array($value)) {
                $class = $value[$this->classKey] ?? $name;
                unset($value[$this->classKey]);
                $obj = $this->makeObject($class, $value);
            }
            if ($refresh) {
                $this->container->set($name, $obj);
            }
        }
    }

    private function getClass(string $type): ReflectionClass
    {
        if (!$this->definition->has($type)) {
            $class = new ReflectionClass($type);
            $this->definition->set($type, $class);
        } else {
            $class = $this->definition->get($type);
        }
        return $class;
    }

    private function getArgs(string $type, ReflectionClass $class): array
    {
        if ($this->args[$type] ?? false) {
            $args = $this->args[$type];
        } else {
            $args = array_map(fn (ReflectionParameter $val): string => $val->getName(), $class->getConstructor()?->getParameters() ?? []);
            $this->args[$type] = $args;
        }
        return $args;
    }

    private function getProps(string $type, ReflectionClass $class): array
    {
        if ($this->propertys[$type] ?? false) {
            return $this->propertys[$type];
        }
        $this->props[$type] = [];
        foreach ($class->getProperties() as $prop) {
            $this->props[$type][$prop->getName()] = $prop;
        }
        return $this->props[$type];
    }

    private function makeObject(string $type, array $params, bool $deep = false): object
    {
        $class = $this->getClass($type);
        $args = $this->getArgs($type, $class);
        $constParams = ArrayHelper::remove($params, $this->funcKey, []);
        $constArgs = [];
        foreach ($args as $i => $p) {
            if (isset($constParams[$i])) {
                $constArgs[$p] = $this->makeValue($constParams[$i]);
            } elseif (isset($params[$p])) {
                $constArgs[$p] = $this->makeValue($params[$p]);
            }
        }
        $obj = $class->newInstanceArgs($constArgs);
        if ($deep) {
            $this->initProperty($obj, $class, $args, $params);
        }
        return $obj;
    }

    public function get(string $name, bool $throwException = true, $default = null): ?object
    {
        try {
            if (!$this->container->has($name)) {
                if ($this->lazy[$name] ?? false) {
                    $this->build([$name => $this->lazy[$name]]);
                } elseif (class_exists($name)) {
                    $this->container->set($name, $this->makeObject($name, []));
                }
            }
            $obj = $this->container->get($name);
            if (!in_array($name, $this->initList)) {
                $key = get_class($obj);
                $class = $this->getClass($key);
                $args = $this->getArgs($key, $class);
                $this->initProperty($obj, $class, $args, $this->lazy[$name] ?? []);
                $this->initList[] = $key;
                $this->initList[] = $name;
            }
            return $obj;
        } catch (Throwable $e) {
            if ($throwException && $default === null) {
                throw $e;
            }
            return $default;
        }
    }

    public function createObject(string|array $type, array $params = [], bool $singleTon = true): object
    {
        if (is_string($type)) {
            if (!class_exists($type)) {
                return $this->get($type);
            }
            return $this->make($type, $params, $singleTon);
        }
        if (!isset($type[$this->classKey])) {
            throw new InvalidArgumentException('Object configuration must be an array containing a "class" element.');
        }
        $class = ArrayHelper::remove($type, $this->classKey);
        $params = [...$type, ...$params];
        return $this->make($class, $params, $singleTon);
    }

    public function make(string $class, array $params = [], bool $singleTon = true): object
    {
        if ($singleTon) {
            if ($this->container->has($class)) {
                return $this->container->get($class);
            }
            $obj = $this->makeObject($class, $params);
            $this->container->set($class, $obj);
        } else {
            $obj = $this->makeObject($class, $params);
        }
        $this->configure($obj, $params);
        return $obj;
    }

    private function makeValue(mixed $val): mixed
    {
        if (is_array($val) && ($val[$this->classKey] ?? false)) {
            $type = ArrayHelper::remove($val, $this->classKey);
            $val = $this->makeObject($type, $val, true);
        } elseif ($val instanceof ArrayDefinition) {
            foreach ($val->items as &$item) {
                if ($type = ArrayHelper::remove($item, $this->classKey)) {
                    $item = $this->makeObject($type, $item, true);
                } elseif ($item instanceof Definition) {
                    $item = $this->get($item->item);
                }
            }
            return $val->items;
        } elseif ($val instanceof Definition) {
            return $this->get($val->item);
        }
        return $val;
    }

    public function configure(object $obj, iterable $params): void
    {
        $type = get_class($obj);
        $class = $this->getClass($type);
        $args = $this->getArgs($type, $class);
        $props = $this->getProps($type, $class);
        foreach ($params as $action => $arguments) {
            if (substr($action, -2) === $this->funcKey && $action !== $this->funcKey && $action !== 'init()') {
                call_user_func_array([$obj, substr($action, 0, -2)], (array)$arguments);
            } elseif (($attribute = $props[$action] ?? false) && !$attribute->isReadOnly()) {
                $val = $this->makeValue($arguments);
                !in_array($action, $args) && $obj->$action = $val;
            }
        }
        if ($obj instanceof InitInterface) {
            if (getCid() === -1) {
                $obj->init();
            } else {
                share("di.init.{$type}", fn () => $obj->init());
            }
        }
    }

    private function initProperty(object $obj, ReflectionClass $class, array $args, iterable $params): void
    {
        $props = $this->getProps(get_class($obj), $class);
        foreach ($params as $property => $v) {
            if (substr($property, -2) === $this->funcKey && $property !== $this->funcKey && $property !== 'init()') {
                call_user_func_array([$obj, substr($property, 0, -2)], $v);
            }
            if (($attribute = $props[$property] ?? false) && !$attribute->isReadOnly() && !in_array($property, $args)) {
                $attribute->setAccessible(true);
                $attribute->setValue($obj, $this->makeValue($v));
            }
        }

        if ($obj instanceof InitInterface) {
            if (getCid() === -1) {
                $obj->init();
            } else {
                $name = get_class($obj);
                share("di.init.{$name}", fn () => $obj->init());
            }
        }
    }
}
