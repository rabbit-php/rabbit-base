<?php

declare(strict_types=1);

namespace Rabbit\Base\DI;

class ArrayDefinition
{
    public function __construct(public array $items)
    {
    }
}
