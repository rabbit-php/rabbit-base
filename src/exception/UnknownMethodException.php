<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/24
 * Time: 15:37
 */

namespace rabbit\exception;
/**
 * Class UnknownMethodException
 * @package rabbit\exception
 */
class UnknownMethodException extends \BadMethodCallException
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Unknown Method';
    }
}