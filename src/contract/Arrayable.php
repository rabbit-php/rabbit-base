<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9
 * Time: 13:42
 */

namespace rabbit\framework\contract;


interface Arrayable
{
    /**
     * @return array
     */
    public function toArray(): array;
}