<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

/**
 * Interface StaticInstanceInterface
 * @package Rabbit\Base\Core
 */
interface StaticInstanceInterface
{
    /**
     * @param bool $refresh
     * @return mixed
     */
    public static function instance(bool $refresh = false);
}
