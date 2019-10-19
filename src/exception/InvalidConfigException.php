<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/24
 * Time: 15:21
 */

namespace rabbit\exception;

use rabbit\core\Exception;

/**
 * Class InvalidConfigException
 * @package rabbit\exception
 */
class InvalidConfigException extends Exception
{
    /**
     * @return string the user-friendly name of this exception
     */
    public function getName(): string
    {
        return 'Invalid Configuration';
    }
}
