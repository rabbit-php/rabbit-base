<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/13
 * Time: 19:28
 */

namespace rabbit\core;

/**
 * Class Exception
 * @package rabbit\core
 */
class Exception extends \Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Exception';
    }
}