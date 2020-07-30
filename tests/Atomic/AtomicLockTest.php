<?php
declare(strict_types=1);

namespace Rabbit\Base\tests\Atomic;


use Rabbit\Base\Atomic\AtomicLock;
use Rabbit\Base\Tests\TestCase;

/**
 * Class AtomicLockTest
 * @package Rabbit\Base\Test\Atomic
 */
class AtomicLockTest extends TestCase
{
    public function testInvoke()
    {
        $lock = new AtomicLock();
        $res = $lock(function () {
            return 1;
        });
        $this->assertEquals($res, 1);
    }
}