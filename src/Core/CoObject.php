<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

trait CoObject
{
    public function __get($name)
    {
        return Context::get(__CLASS__ . '.' . $name);
    }

    public function __set($name, $value)
    {
        Context::set(__CLASS__ . '.' . $name, $value);
    }
}
