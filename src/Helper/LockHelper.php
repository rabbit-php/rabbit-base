<?php
declare(strict_types=1);

namespace Rabbit\Base\Helper;

use Rabbit\Base\Contract\LockInterface;
use Rabbit\Base\Core\Exception;

/**
 * Class LockHelper
 * @package Rabbit\Base\Helper
 */
class LockHelper
{
    /** @var array */
    private static array $locks = [];

    /**
     * @param string $name
     * @param LockInterface $lock
     * @throws Exception
     */
    public static function add(string $name, LockInterface $lock): void
    {
        if (isset(self::$locks[$name])) {
            throw new Exception("Lock $name already exists");
        }
        self::$locks[$name] = $lock;
    }

    /**
     * @param string $name
     * @return LockInterface|null
     */
    public static function getLock(string $name): ?LockInterface
    {
        if (isset(self::$locks[$name])) {
            return self::$locks[$name];
        }
        return null;
    }
}
