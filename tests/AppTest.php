<?php
declare(strict_types=1);

namespace Rabbit\Base\Tests;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\Log\Test\TestLogger;
use Rabbit\Base\App;
use Rabbit\Base\Core\ObjectFactory;

/**
 * Class AppTest
 * @package Rabbit\Base\Tests
 */
class AppTest extends TestCase
{
    public function testGetAlais()
    {
        $this->assertIsString(App::getAlias('@root'));
        $this->assertEquals(App::getAlias('test'), 'test');
    }

    public function testSetAlias()
    {
        App::setAlias('@test', __DIR__);
        $this->assertEquals(__DIR__, App::getAlias('@test'));
        App::setAlias('test1', __DIR__);
        $this->assertEquals(__DIR__, App::getAlias('@test1'));
        App::setAlias('test1', null);
        $this->assertNull(App::getAlias('@test1', false));
        $this->expectException('\InvalidArgumentException');
        App::getAlias('@test1');
    }

    public function testGetLogger()
    {
        $this->expectExceptionMessage('Call to a member function get() on null');
        App::getLogger();
    }

    public function testGetLoggerWithInit()
    {
        ObjectFactory::getContainer();
        $this->assertInstanceOf(LoggerInterface::class, App::getLogger());
        $this->assertInstanceOf(NullLogger::class, App::getLogger());
    }

    public function testSetLogger()
    {
        App::setLogger(new TestLogger());
        $this->assertInstanceOf(TestLogger::class, App::getLogger());
    }

    public function testDebug()
    {
        $logger = new TestLogger();
        App::setLogger($logger);
        $this->assertFalse($logger->hasDebug('test debug'));
        App::debug('test debug');
        $this->assertTrue($logger->hasDebug('test debug'));
    }

    public function testInfo()
    {
        $logger = new TestLogger();
        App::setLogger($logger);
        $this->assertFalse($logger->hasInfo('test info'));
        App::info('test info');
        $this->assertTrue($logger->hasInfo('test info'));
    }

    public function testWarning()
    {
        $logger = new TestLogger();
        App::setLogger($logger);
        $this->assertFalse($logger->hasWarning('test warning'));
        App::warning('test warning');
        $this->assertTrue($logger->hasWarning('test warning'));
    }

    public function testError()
    {
        $logger = new TestLogger();
        App::setLogger($logger);
        $this->assertFalse($logger->hasError('test error'));
        App::error('test error');
        $this->assertTrue($logger->hasError('test error'));
    }

    public function testEmergency()
    {
        $logger = new TestLogger();
        App::setLogger($logger);
        $this->assertFalse($logger->hasEmergency('test emergency'));
        App::emergency('test emergency');
        $this->assertTrue($logger->hasEmergency('test emergency'));
    }

    public function testNotice()
    {
        $logger = new TestLogger();
        App::setLogger($logger);
        $this->assertFalse($logger->hasNotice('test notice'));
        App::notice('test notice');
        $this->assertTrue($logger->hasNotice('test notice'));
    }

    public function testAlert()
    {
        $logger = new TestLogger();
        App::setLogger($logger);
        $this->assertFalse($logger->hasAlert('test alert'));
        App::alert('test alert');
        $this->assertTrue($logger->hasAlert('test alert'));
    }

    public function testCritical()
    {
        $logger = new TestLogger();
        App::setLogger($logger);
        $this->assertFalse($logger->hasCritical('test critical'));
        App::critical('test critical');
        $this->assertTrue($logger->hasCritical('test critical'));
    }
}