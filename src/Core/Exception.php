<?php
declare(strict_types=1);

namespace Rabbit\Base\Core;

/**
 * Class Exception
 * @package Rabbit\Base\Core
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
