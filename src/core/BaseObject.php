<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/24
 * Time: 20:27
 */

namespace rabbit\core;

/**
 * Class BaseObject
 * @package rabbit\core
 */
class BaseObject
{
    /**
     * @param $name
     */
    public function __get($name)
    {
        $this->$name;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->$name = $value;
    }
}