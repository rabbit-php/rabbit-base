<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

trait SingleTrait
{
    protected static $instance = null;

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }
}
