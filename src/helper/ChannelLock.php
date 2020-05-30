<?php
declare(strict_types=1);

namespace rabbit\helper;

use Co\Channel;

/**
 * Class ChannelLock
 * @package rabbit\helper
 */
class ChannelLock
{
    /** @var Channel */
    private $chan;

    /**
     * ChannelLock constructor.
     */
    public function __construct()
    {
        $this->chan = new Channel(1);
    }

    /**
     * @param callable $callback
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