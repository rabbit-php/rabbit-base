<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use ArrayObject;
use Swow\Coroutine as SwowCoroutine;

class Coroutine extends SwowCoroutine
{

    private ?ArrayObject $context = null;

    public function __destruct()
    {
        $this->context = null;
    }

    public function getContext(): ArrayObject
    {
        return $this->context ?? ($this->context = new ArrayObject());
    }

    public function setContext(string $name, $value): void
    {
        $this->context[$name] = $value;
    }
}
