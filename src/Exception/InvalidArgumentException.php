<?php
declare(strict_types=1);

namespace Rabbit\Base\Exception;

/**
 * Class InvalidArgumentException
 * @package Rabbit\Base\Exception
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
