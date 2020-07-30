<?php
declare(strict_types=1);

namespace Rabbit\Base\Tests\Core;

use Rabbit\Base\Core\Loop;
use Rabbit\Base\Core\Timer;
use Rabbit\Base\Tests\TestCase;
use Swoole\Event;
use function Co\Run;

/**
 * Class testLoop
 * @package Rabbit\Base\Tests\Core
 */
class LoopTest extends TestCase
{
    public function testAddEventNoParam()
    {
        $this->expectExceptionMessage('swoole event must have 4 param');
        Loop::addEvent('test', [null, SWOOLE_EVENT_READ | SWOOLE_EVENT_WRITE], true);
    }

    public function testAddEventNoResource()
    {
        $this->expectExceptionMessage('swoole event the 1th param must be resource');
        Loop::addEvent('test', [null, function (string $id) {
        }, function () {
        }, SWOOLE_EVENT_READ | SWOOLE_EVENT_WRITE], true);
    }

    public function testAddEventNoCallable()
    {
        $fp = fopen('test', 'w+');
        unlink('test');
        $this->expectExceptionMessage('swoole event the 2th param must be null or callable');
        Loop::addEvent('test', [$fp, 'test', 'test', SWOOLE_EVENT_READ | SWOOLE_EVENT_WRITE], true);
    }

    public function testAddEventBothRWEmpty()
    {
        $fp = fopen('test', 'w+');
        unlink('test');
        $this->expectExceptionMessage('swoole event both read and write callbacks are empty');
        Loop::addEvent('test', [$fp, null, null, SWOOLE_EVENT_READ | SWOOLE_EVENT_WRITE], true);
    }

    public function testAddEvnetNoEventType()
    {
        $fp = fopen('test', 'w+');
        unlink('test');
        $this->expectExceptionMessage('swoole event the 4th param must be SWOOLE_EVENT_READ or SWOOLE_EVENT_WRITE or (SWOOLE_EVENT_READ | SWOOLE_EVENT_WRITE)');
        Loop::addEvent('test', [$fp, function () {
        }, null, 1], true);
    }

    public function testAddRunStopEvent()
    {
        Loop::run('test');
        $fp = stream_socket_client("tcp://www.qq.com:80", $errno, $errstr, 30);
        Loop::addEvent('test', [$fp, function (string $id) use ($fp) {
            $res = (string)fread($fp, 8192);
            Loop::stopEvent('test', $id);
            $this->assertIsString($res);
        }, function () use ($fp) {
            fwrite($fp, "GET / HTTP/1.1\r\nHost: www.qq.com\r\n\r\n");
        }, SWOOLE_EVENT_READ | SWOOLE_EVENT_WRITE], true);
        Event::wait();
    }

    public function testRemoveEvent()
    {
        Loop::run('test');
        $fp = stream_socket_client("tcp://www.qq.com:80", $errno, $errstr, 30);
        Loop::addEvent('test', [$fp, function (string $id) use ($fp) {
            $res = (string)fread($fp, 8192);
            Loop::removeEvent('test', $id);
            $this->assertIsString($res);
        }, function () use ($fp) {
            fwrite($fp, "GET / HTTP/1.1\r\nHost: www.qq.com\r\n\r\n");
        }, SWOOLE_EVENT_READ | SWOOLE_EVENT_WRITE], true);
        Event::wait();
    }

    public function testAddTimerNoParams()
    {
        $this->expectExceptionMessage('swoole timer only support 2 params');
        Loop::addTimer('test', [null], true);
    }

    public function testAddTimerNoIntOrFloat()
    {
        $this->expectExceptionMessage('swoole timer the 1th param must be int or float');
        Loop::addTimer('test', ['test', null], true);
    }

    public function testAddTimerNoCallable()
    {
        $this->expectExceptionMessage('swoole timer the 2th param must be callable');
        Loop::addTimer('test', [1000, null], true);
    }

    public function testAddTimerAndRunAndStop()
    {
        Run(function () {
            $id = Loop::addTimer('test', [100, function ($id) {
                $this->assertIsString($id);
                Loop::stopTimer('test', $id);
            }], true);
            $timers = Timer::getTimers();
            $this->assertTrue(isset($timers['test.timer.' . $id]));
        });
    }

    public function testRemoveTimer()
    {
        Run(function () {
            $id = Loop::addTimer('test', [100, function ($id) {
                $this->assertIsString($id);
                Loop::removeTimer('test', $id);
            }], true);
            $timers = Timer::getTimers();
            $this->assertTrue(isset($timers['test.timer.' . $id]));
        });
    }
}