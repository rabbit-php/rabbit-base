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
        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        }
        return $this->$name;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
            return;
        }
        $this->$name = $value;
    }
}