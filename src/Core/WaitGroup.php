<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Throwable;
use Rabbit\Base\App;
use BadMethodCallException;
use InvalidArgumentException;
use Rabbit\Base\Helper\ExceptionHelper;

/**
 * Class WaitGroup
 * @package Rabbit\Base\Core
 */
class WaitGroup
{
    protected $chan;

    protected int $count = 0;

    protected bool $waiting = false;

    public function __construct()
    {
        $this->chan = makeChannel(1);
    }

    /**
     * @Author Albert 63851587@qq.com
     * @DateTime 2020-10-27
     * @param callable $function
     * @return void
     */
    public function add(callable $function)
    {
        if ($this->waiting) {
            throw new BadMethodCallException('WaitGroup misuse: add called concurrently with wait');
        }
        $count = $this->count + 1;
        if ($count < 0) {
            throw new InvalidArgumentException('WaitGroup misuse: negative counter');
        }
        $this->count = $count;
        return rgo(function () use ($function): void {
            try {
                $function();
            } catch (Throwable $throwable) {
                App::error(ExceptionHelper::dumpExceptionToString($throwable));
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

    public function wait(int $timeout = -1): bool
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
