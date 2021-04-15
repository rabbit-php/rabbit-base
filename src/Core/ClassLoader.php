<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Composer\Autoload\ClassLoader as AutoloadClassLoader;

class ClassLoader
{
    private AutoloadClassLoader $loader;

    private static $isInit = false;

    public function __construct(AutoloadClassLoader $loader)
    {
        $this->loader = $loader;
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
