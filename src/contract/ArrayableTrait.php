<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/26
 * Time: 20:25
 */

namespace rabbit\contract;

/**
 * Class AbstractArray
 * @package rabbit\contract
 */
trait ArrayableTrait
{
    /** @var array */
    protected $attributes = [];

    /**
     * @param $name
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
