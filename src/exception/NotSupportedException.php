<?php
declare(strict_types=1);

namespace Rabbit\Base\Exception;

use Rabbit\Base\Core\Exception;

/**
 * Class NotSupportedException
 * @package Rabbit\Base\Exception
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
