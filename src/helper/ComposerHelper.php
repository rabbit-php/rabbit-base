<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9
 * Time: 22:26
 */

namespace rabbit\framework\helper;


use Composer\Autoload\ClassLoader;

class ComposerHelper
{
    /**
     * @var ClassLoader|mixed
     */
    static $loader;

    /**
     * @return ClassLoader
     */
    public static function getLoader(): ClassLoader
    {
        if (!self::$loader) {
            $loader = self::findLoader();
            $loader instanceof ClassLoader && self::$loader = $loader;
        }
        return self::$loader;
    }

    /**
     * @return ClassLoader
     * @throws \RuntimeException When Composer loader not found
     */
    public static function findLoader(): ClassLoader
    {
        $composerClass = '';
        foreach (get_declared_classes() as $declaredClass) {
            if (strpos($declaredClass, 'ComposerAutoloaderInit') === 0 && method_exists($declaredClass, 'getLoader')) {
                $composerClass = $declaredClass;
                break;
            }
        }
        if (!$composerClass) {
            throw new \RuntimeException('Composer loader not found.');
        }
        return $composerClass::getLoader();
    }
}