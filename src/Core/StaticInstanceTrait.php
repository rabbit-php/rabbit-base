<?php
declare(strict_types=1);

namespace Rabbit\Base\Core;

use DI\DependencyException;
use DI\NotFoundException;
use ReflectionException;

/**
 * Trait StaticInstanceTrait
 * @package Rabbit\Base\Core
 */
trait StaticInstanceTrait
{
    /**
     * @var static[] static instances in format: `[className => object]`
     */
    private static array $_instances = [];


    /**
     * @param bool $refresh
     * @return mixed|StaticInstanceTrait
     * @throws DependencyException
     * @throws NotFoundException|ReflectionException
     */
    public static function instance($refresh = false)
    {
        $className = get_called_class();
        if ($refresh || !isset(self::$_instances[$className])) {
            self::$_instances[$className] = create($className);
        }
        return self::$_instances[$className];
    }
}
