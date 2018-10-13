<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9
 * Time: 11:56
 */

namespace rabbit\framework\contract;


interface DispatcherInterface
{
    /**
     * Dispatch
     *
     * @param array ...$params dispatcher params
     */
    public function dispatch(...$params);
}