<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/20
 * Time: 20:14
 */

namespace rabbit\exception;


use rabbit\core\Exception;

/**
 * Class NotSupportedException
 * @package rabbit\exception
 */
class NotSupportedException extends Exception
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return 'Not Supported';
    }
}