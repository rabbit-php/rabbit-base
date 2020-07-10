<?php
declare(strict_types=1);

namespace Rabbit\Base\Helper;

use Rabbit\Base\Core\Exception;
use Swoole\Atomic;

/**
 * Class AtomicHelper
 * @package Rabbit\Base\Helper
 */
class AtomicHelper
{
    /** @var array */
    private static array $atomics = [];

    /**
     * @param string $name
     * @param Atomic $atomic
     * @throws Exception
     */
    public static function addAtomic(string $name, Atomic $atomic): void
    {
        if (isset(self::$atomics[$name])) {
            throw new Exception("Atomic $name already exists");
        }
        self::$atomics[$name] = $atomic;
    }

    /**
     * @param string $name
     * @return Atomic|null
     */
    public static function getAtomic(string $name): ?Atomic
    {
        if (isset(self::$atomics[$name])) {
            return self::$atomics[$name];
        }
        return null;
    }
}
