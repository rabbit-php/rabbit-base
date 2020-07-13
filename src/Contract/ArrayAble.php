<?php
declare(strict_types=1);

namespace Rabbit\Base\Contract;

/**
 * Interface ArrayAble
 * @package Rabbit\Base\Contract
 */
interface ArrayAble
{
    /**
     * @return array
     */
    public function toArray(): array;
}
