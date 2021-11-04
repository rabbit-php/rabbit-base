<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Closure;
use Throwable;
use RuntimeException;

class ShareResult
{
    protected Channel $channel;

    public $result;

    protected int $timeout;

    protected string $key;

    protected ?Throwable $e = null;

    public static array $shares = [];

    protected int $count = 0;

    public function __construct(string $key, int $timeout = 3)
    {
        $this->channel = new Channel();
        $this->timeout = $timeout;
        $this->key = $key;
        if (self::$shares[$key] ?? false) {
            throw new RuntimeException("$key is exists!");
        }
        self::$shares[$key] = $this;
    }

    public static function getShare(string $key, int $timeout): self
    {
        if (self::$shares[$key] ?? false) {
            return ShareResult::$shares[$key];
        }
        return new static($key, $timeout);
    }

    public function getStatus(): int
    {
        return $this->channel->errCode;
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function __invoke(Closure $function): self
    {
        $this->count++;
        try {
            $this->channel->push(1, $this->timeout);
            if ($this->channel->errCode === SWOOLE_CHANNEL_CLOSED) {
                if ($this->e !== null) {
                    throw $this->e;
                }
                return $this;
            }
            $this->result = call_user_func($function);
            return $this;
        } catch (Throwable $throwable) {
            $this->e = $throwable;
            throw $throwable;
        } finally {
            unset(self::$shares[$this->key]);
            $this->channel->close();
        }
    }
}
