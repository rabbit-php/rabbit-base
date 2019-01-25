<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/24
 * Time: 15:36
 */

namespace rabbit\exception;

/**
 * Class InvalidArgumentException
 * @package rabbit\exception
 */
class InvalidArgumentException extends \BadMethodCallException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Invalid Argument';
    }
}