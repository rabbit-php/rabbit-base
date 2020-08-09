<?php
declare(strict_types=1);

namespace Swoole\Coroutine;

use Rabbit\Base\Helper\ExceptionHelper;
use Swoole\Coroutine\Channel;

/**
 * Class WaitGroup
 * @package Swoole\Coroutine
 */
class WaitGroup
{
    protected Channel $chan;

    protected int $count = 0;

    protected bool $waiting = false;

    public function __construct()
    {
        $this->chan = new Channel(1);
    }

    public function add(callable $function): int
    {
        if ($this->waiting) {
            throw new BadMethodCallException('WaitGroup misuse: add called concurrently with wait');
        }
        $count = $this->count++;
        if ($count < 0) {
            throw new InvalidArgumentException('WaitGroup misuse: negative counter');
        }
        $this->count = $count;
        return go(function () use ($function): void {
            try {
                $function();
            } catch (Throwable $throwable) {
                print_r(ExceptionHelper::convertExceptionToArray($throwable));
            } finally {
                $this->done();
            }
        });
    }

    public function done(): void
    {
        $count = $this->count - 1;
        if ($count < 0) {
            throw new BadMethodCallException('WaitGroup misuse: negative counter');
        }
        $this->count = $count;
        if ($count === 0 && $this->waiting) {
            $this->chan->push(true);
        }
    }

    public function wait(float $timeout = -1): bool
    {
        if ($this->waiting) {
            throw new BadMethodCallException('WaitGroup misuse: reused before previous wait has returned');
        }
        if ($this->count > 0) {
            $this->waiting = true;
            $done = $this->chan->pop($timeout);
            $this->waiting = false;
            return $done;
        }
        return true;
    }
}