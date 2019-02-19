<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/20
 * Time: 1:32
 */

namespace rabbit\core;


use rabbit\helper\ArrayHelper;

trait AttributeTrait
{
    /** @var array */
    protected $attributes = [];

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
     * @return UserInterface|void
     */
    public function withAttributes(array $attributes): self
    {
        $this->attributes = $attributes;
    }

    /**
     * @param string $name
     * @param $value
     * @return User
     */
    public function withAttribute(string $name, $value): self
    {
        $this->attributes[$name] = $value;
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
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        return ArrayHelper::getValue($this->attributes, $name);
    }
}