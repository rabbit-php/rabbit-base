<?php
declare(strict_types=1);

namespace Rabbit\Base\Core;

use Rabbit\Base\Helper\ArrayHelper;

/**
 * Trait AttributeTrait
 * @package Rabbit\Base\Core
 */
trait AttributeTrait
{
    /** @var array */
    protected array $attributes = [];

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param string $name
     * @param null $default
     * @return mixed|null
     */
    public function getAttribute(string $name, $default = null)
    {
        return ArrayHelper::getValue($this->attributes, $name, $default);
    }

    /**
     * @param array $attributes
     * @return AttributeTrait
     */
    public function withAttributes(array $attributes): self
    {
        $this->attributes = $attributes;
        return $this;
    }

    /**
     * @param string $name
     * @param $value
     * @return AttributeTrait
     */
    public function withAttribute(string $name, $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return ArrayHelper::getValue($this->attributes, $name);
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }
}
