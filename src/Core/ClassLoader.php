<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Composer\Autoload\ClassLoader as AutoloadClassLoader;

final class ClassLoader
{
    private static $isInit = false;

    public function __construct(private AutoloadClassLoader $loader)
    {
    }

    public function loadClass($class)
    {
        schedule([$this->loader, 'loadClass'], $class);
    }

    public static function PreemptiveLoader(AutoloadClassLoader $loader): void
    {
        if (!self::$isInit) {
            $loader->unregister();
            spl_autoload_register(array(new static($loader), 'loadClass'));
        }
    }
}
