<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/13
 * Time: 16:08
 */

namespace rabbit\framework\handler;


interface ResponseHandlerInterface
{
    public function handler(ResponseHandlerInterface $response);
}