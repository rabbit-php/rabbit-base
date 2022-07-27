<?php

declare(strict_types=1);

namespace Rabbit\Base\DI;

use Psr\Container\ContainerInterface;
use Rabbit\Base\Exception\InvalidConfigException;
use RuntimeException;

class Container implements ContainerInterface
{
    private array $map = [];

    public function get(string $id)
    {
        $id = strtolower($id);
        if (isset($this->map[$id])) {
            return $this->map[$id];
        }
        throw new InvalidConfigException("{$id} not found in container");
    }

    public function has(string $id): bool
    {
        $id = strtolower($id);
        return isset($this->map[$id]);
    }

    public function set(string $id, mixed $value): void
    {
        $id = strtolower($id);
        if (isset($this->map[$id])) {
            throw new RuntimeException("{$id} is in container");
        }
        $this->map[$id] = $value;
    }
}
