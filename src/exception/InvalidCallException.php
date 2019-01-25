<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/24
 * Time: 15:36
 */

namespace rabbit\exception;

/**
 * Class InvalidCallException
 * @package rabbit\exception
 */
class InvalidCallException extends \BadMethodCallException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Invalid Call';
    }
}