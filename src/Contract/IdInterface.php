<?php

declare(strict_types=1);

namespace Rabbit\Base\Contract;

/**
 * Interface IdInterface
 * @package Rabbit\Base\Contract
 */
interface IdInterface
{
    /**
     * @return mixed
     */
    public function nextId();
}
