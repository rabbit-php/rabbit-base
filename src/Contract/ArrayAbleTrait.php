<?php
declare(strict_types=1);

namespace Rabbit\Base\Contract;

/**
 * Trait ArrayAbleTrait
 * @package Rabbit\Base\Contract
 */
trait ArrayAbleTrait
{
    /** @var array */
    protected array $attributes = [];

    /**
     * @param $name
     * @return mixed|null
     */
    public function __get($name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
