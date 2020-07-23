<?php
declare(strict_types=1);

namespace Rabbit\Base\Atomic;

use Closure;
use Rabbit\Base\Contract\LockInterface;
use Rabbit\Base\Helper\ExceptionHelper;
use Swoole\Atomic;
use Throwable;

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
    public function __invoke(Closure $function, string $name = '', float $timeout = 0.001)
    {
        try {
            while ($this->atomic->get() !== 0) {
                \Co::sleep($timeout);
            }
            $this->atomic->add();
            return call_user_func($function, ...$params);
        } catch (Throwable $throwable) {
            print_r(ExceptionHelper::convertExceptionToArray($throwable));
        } finally {
            $this->atomic->sub();
        }
    }
}
