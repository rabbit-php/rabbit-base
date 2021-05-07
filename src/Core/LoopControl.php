<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Swoole\Coroutine;

final class LoopControl
{

    public static $loopArr = [];

    public int $sleep = 1;

    private int $cid = 0;

    private string $name;

    private bool $run = true;

    public bool $loop = true;

    public function __construct(int $sleep, string $name = null)
    {
        $this->sleep = $sleep;
        $this->name = $name ?? uniqid();
        self::$loopArr[] = $this;
    }

    public static function shutdown(): void
    {
        foreach (self::$loopArr as $loop) {
            $loop->loop = false;
        }
    }

    public function getRun(): bool
    {
        return $this->run;
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

    public function check(): void
    {
        if ($this->run === false) {
            Coroutine::yield();
        }
    }

    public function stop(): bool
    {
        if ($this->run === true) {
            $this->run = false;
            return true;
        }
        return false;
    }

    public function start(): bool
    {
        if ($this->run === false) {
            $this->run = true;
            Coroutine::resume($this->cid);
            return true;
        }
        return false;
    }
}
