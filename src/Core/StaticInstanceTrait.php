<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

trait StaticInstanceTrait
{
    private static array $_instances = [];

    public static function instance(bool $refresh = false)
    {
        $className = get_called_class();
        if ($refresh || !isset(self::$_instances[$className])) {
            self::$_instances[$className] = create($className);
        }
        return self::$_instances[$className];
    }
}
