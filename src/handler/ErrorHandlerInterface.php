<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/13
 * Time: 15:45
 */

namespace rabbit\handler;


interface ErrorHandlerInterface
{
    public function handle(ErrorHandlerInterface $handler);
}