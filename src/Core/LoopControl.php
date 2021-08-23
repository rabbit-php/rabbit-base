<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Swoole\Coroutine;

final class LoopControl
{

    public static array $loopArr = [];

    public int $sleep = 1;

    private int $cid = 0;

    private string $name;

    public bool $loop = true;

    private bool $yielded = false;

    public function __construct(int $sleep = 1, string $name = null)
    {
        $this->sleep = $sleep;
        $this->name = $name ?? uniqid();
        self::$loopArr[] = $this;
    }

    public function shutdown(): void
    {
        $this->loop = false;
        $this->start();
        Coroutine::cancel($this->cid);
    }

    public static function shutdownAll(): void
    {
        foreach (self::$loopArr as $loop) {
            $loop->shutdown();
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCid(): int
    {
        return $this->cid;
    }

    public function setCid(int $cid): void
    {
        if ($this->cid === 0) {
            $this->cid = $cid;
        }
    }

    public function stop(): void
    {
        if ($this->yielded === false && $this->loop === true) {
            $this->yielded = true;
            Coroutine::yield();
        }
    }

    public function start(): bool
    {
        if ($this->yielded === true) {
            $this->yielded = false;
            Coroutine::resume($this->cid);
            return true;
        }
        return false;
    }
}
