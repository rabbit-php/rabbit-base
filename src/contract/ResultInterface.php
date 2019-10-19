<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/17
 * Time: 17:52
 */

namespace rabbit\contract;

/**
 * Interface ResultInterface
 * @package rabbit\contract
 */
interface ResultInterface
{
    /**
     * @param mixed ...$params
     * @return mixed
     */
    public function getResult(...$params);
}
