<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Throwable;
use Swow\Channel as SwowChannel;

class Channel extends SwowChannel
{
    public function pop(int $timeout = -1)
    {
        $timeout > 0 && $timeout *= 1000;
        try {
            return parent::pop($timeout);
        } catch (Throwable $e) {
            return false;
        }
    }
}
