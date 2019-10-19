<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/24
 * Time: 10:05
 */

namespace rabbit\contract;

/**
 * Interface IdGennerator
 * @package rabbit\contract
 */
interface IdGennerator
{
    /**
     * @return mixed
     */
    public function create();
}
