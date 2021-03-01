<?php

declare(strict_types=1);

namespace Rabbit\Base\Atomic;

use Closure;
use Throwable;
use Swoole\Atomic;
use Rabbit\Base\App;
use Rabbit\Base\Contract\LockInterface;
use Rabbit\Base\Helper\ExceptionHelper;

/**
 * Class AtomicLock
 * @package Rabbit\Base\atomic
 */
class AtomicLock implements LockInterface
{
    /** @var Atomic */
    protected Atomic $atomic;

    /**
     * AtomicLock constructor.
     */
    public function __construct()
    {
        $this->atomic = new Atomic();
    }

    /**
     * @param Closure $function
     * @param string $name
     * @param float $timeout
     * @return mixed
     * @throws Throwable
     */
    public function __invoke(Closure $function, bool $next = true, string $name = '', float $timeout = 0.001)
    {
        try {
            while ($this->atomic->get() !== 0) {
                if ($next) {
                    usleep($timeout * 1000);
                } else {
                    return false;
                }
            }
            $this->atomic->add();
            $result = call_user_func($function);
            $this->atomic->sub();
            return $result;
        } catch (Throwable $throwable) {
            App::error(ExceptionHelper::dumpExceptionToString($throwable));
            $this->atomic->sub();
        }
    }
}
