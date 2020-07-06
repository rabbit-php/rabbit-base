<?php
declare(strict_types=1);

namespace Rabbit\Base\Exception;

/**
 * Class InvalidCallException
 * @package Rabbit\Base\Exception
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
