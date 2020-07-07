<?php
declare(strict_types=1);

namespace Rabbit\Base\Exception;

/**
 * Class UnknownMethodException
 * @package Rabbit\Base\Exception
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
