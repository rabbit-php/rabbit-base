<?php
declare(strict_types=1);

namespace Rabbit\Base\Helper;

use Co\Channel;

/**
 * Class ChannelLock
 * @package Rabbit\Base\Helper
 */
class ChannelLock
{
    /** @var Channel */
    private Channel $chan;

    /**
     * ChannelLock constructor.
     */
    public function __construct()
    {
        $this->chan = new Channel(1);
    }

    /**
     * @param callable $callback
     * @param float|null $timeout
     * @return mixed
     */
    public function lock(callable $callback, ?float $timeout = null)
    {
        $this->chan->push(1);
        try {
            return call_user_func($callback);
        } finally {
            $this->chan->pop($timeout);
        }
    }
}