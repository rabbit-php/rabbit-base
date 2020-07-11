<?php
declare(strict_types=1);

namespace Rabbit\Base\Contract;

use Closure;

/**
 * Interface LockInterface
 * @package Rabbit\Base\Contract
 */
interface LockInterface
{
    /**
     * @param Closure $function
     * @param string $name
     * @param float $timeout
     * @return mixed
     */
    public function __invoke(Closure $function, string $name = '', float $timeout = 600);
}
