<?php

declare(strict_types=1);

namespace Rabbit\Base\Helper;

use Rabbit\Base\App;
use Rabbit\Base\Contract\LockInterface;
use Rabbit\Base\Core\Exception;

class LockHelper
{
    private static array $locks = [];

    public static function add(string $name, LockInterface $lock): bool
    {
        if (isset(self::$locks[$name])) {
            App::warning("Lock $name already exists");
            return false;
        }
        self::$locks[$name] = $lock;
        return true;
    }

    public static function getLock(string $name): ?LockInterface
    {
        if (isset(self::$locks[$name])) {
            return self::$locks[$name];
        }
        return null;
    }
}
