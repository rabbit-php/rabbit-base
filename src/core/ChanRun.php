<?php
declare(strict_types=1);

namespace Rabbit\Base\Core;

use Co\Channel;
use rabbit\App;

/**
 * Class ChanRun
 * @package rabbit\core
 */
class ChanRun
{
    /** @var Channel */
    protected Channel $channel;
    /** @var bool */
    protected bool $running = false;
    /** @var callable */
    protected $callback;

    /**
     * ChanRun constructor.
     * @param callable $callback
     * @param int|null $size
     */
    public function __construct(callable $callback, int $size = null)
    {
        $this->channel = new Channel($size);
        $this->callback = $callback;
    }

    /**
     * @param $data
     * @return bool
     */
    public function push($data): bool
    {
        return $this->channel->push($data);
    }

    /**
     * @param callable|null $call
     * @return int
     * @throws Exception
     * @throws \Exception
     */
    public function process(?callable $call = null): int
    {
        if ($this->running) {
            $msg = "process already running";
            App::warning($msg);
            throw new Exception($msg);
        }
        $this->running = true;
        return rgo(function () use ($call) {
            while ($this->running) {
                $data = $this->channel->pop();
                rgo(function () use ($call, $data) {
                    $result = call_user_func($this->callback, $data);
                    if (is_callable($call)) {
                        call_user_func($call, $result);
                    }
                });
            }
        });
    }
}