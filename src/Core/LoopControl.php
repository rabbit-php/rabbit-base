<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

use Swoole\Coroutine;

final class LoopControl
{
    public int $sleep = 1 * 1000;

    private int $cid = 0;

    private string $name;

    private bool $run = true;

    public function __construct(int $sleep, string $name = null)
    {
        $this->sleep = $sleep;
        $this->name = $name ?? uniqid();
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

    public function stop(): void
    {
        $this->run = false;
    }

    public function start(): void
    {
        if ($this->run === false) {
            $this->run = true;
            Coroutine::resume($this->cid);
        }
    }
}
