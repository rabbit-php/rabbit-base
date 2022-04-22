<?php

declare(strict_types=1);

namespace Rabbit\Base\Contract;

use Closure;

interface LockInterface
{
    public function __invoke(string $name, Closure $function,  bool $next = true, float $timeout = 600): void;
}
