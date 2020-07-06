<?php
declare(strict_types=1);

namespace Rabbit\Base\Exception;

use Rabbit\Base\Core\Exception;

/**
 * Class InvalidConfigException
 * @package Rabbit\Base\Exception
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
