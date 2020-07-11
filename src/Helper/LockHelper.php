<?php
declare(strict_types=1);

namespace Rabbit\Base\Helper;

use Closure;
use Co\Channel;
use Rabbit\Base\atomic\AtomicLock;
use Throwable;

/**
 * Class LockHelper
 * @package Rabbit\Base\Helper
 */
class LockHelper
{
    const TYPE_PROCESS = 0;
    const TYPE_DISTRIBUTED = 1;
    static ?string $distributedLock = null;

    /**
     * @param string $class
     */
    public function setDistributedLock(string $class): void
    {
        self::$distributedLock = $class;
    }

    /**
     * @param Closure $function
     * @param int|null $type
     * @param string $name
     * @param float|int $timeout
     * @return mixed
     * @throws Throwable
     */
    public static function lock(Closure $function, int $type = self::TYPE_PROCESS, string $name = '', float $timeout = 600)
    {
        static $disChan, $proChan;
        $disChan = new Channel();
        $proChan = new Channel();
        if ($type === self::TYPE_DISTRIBUTED) {
            if (!$disChan->isEmpty()) {
                $lock = $disChan->pop();
            } else {
                $lock = new self::$distributedLock();
            }
        } else {
            if (!$proChan->isEmpty()) {
                $lock = $proChan->pop();
            } else {
                $lock = new AtomicLock();
            }
        }
        return $lock($function, $name, $timeout);
    }
}
