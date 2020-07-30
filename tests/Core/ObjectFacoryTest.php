<?php
declare(strict_types=1);

namespace Rabbit\Base\Tests\Core;

use PHPUnit\Util\ErrorHandler;
use Rabbit\Base\Core\BaseObject;
use Rabbit\Base\Core\ObjectFactory;
use Rabbit\Base\Tests\TestCase;
use function DI\get;

/**
 * Class ObjectFacoryTest
 * @package Rabbit\Base\Tests\Core
 */
class ObjectFacoryTest extends TestCase
{
    public function testGetNotThrow()
    {
        $this->assertNull(ObjectFactory::get('baseObject', false));
    }

    public function testGetNotContainer()
    {
        $this->expectException('Throwable');
        ObjectFactory::get('baseObject');
    }

    public function testGetException()
    {
        $this->expectExceptionMessage('No entry or class found for \'baseObject\'');
        ObjectFactory::getContainer();
        ObjectFactory::get('baseObject');
    }

    public function testSetDefinitions()
    {
        $this->assertEmpty(ObjectFactory::getDefinitions());
        $arr = [
            'errorHandler' => get(ErrorHandler::class)
        ];
        ObjectFactory::setDefinitions($arr);
        $this->assertArrayHasKey('default', ObjectFactory::getDefinitions());
    }

    public function testCreateWithNoSingleTon()
    {
        $obj = ObjectFactory::createObject([
            'class' => BaseObject::class,
            'name' => 'test'
        ], [], false);
        $this->assertInstanceOf(BaseObject::class, $obj);
        $this->assertEquals('test', $obj->name);
        $obj = ObjectFactory::createObject([
            'class' => BaseObject::class,
            'name' => 'test1'
        ], [], false);
        $this->assertEquals('test1', $obj->name);
    }

    public function testCreateWithClass()
    {
        $obj = ObjectFactory::createObject([
            'class' => BaseObject::class,
            'name' => 'test'
        ], [], false);
        $this->assertInstanceOf(BaseObject::class, $obj);
        $this->assertEquals('test', $obj->name);
    }

    public function testCreateWithArray()
    {
        $obj = ObjectFactory::createObject(BaseObject::class, ['name' => 'test'], false);
        $this->assertInstanceOf(BaseObject::class, $obj);
        $this->assertEquals('test', $obj->name);
    }

    public function testCreateWithSingleTon()
    {
        $obj = ObjectFactory::createObject([
            'class' => BaseObject::class,
            'name' => 'test'
        ]);
        $this->assertEquals('test', $obj->name);
        $obj = ObjectFactory::createObject([
            'class' => BaseObject::class,
            'name' => 'test1'
        ]);
        $this->assertEquals('test', $obj->name);
    }

    public function testSetPreInit()
    {
        $arr = [
            'baseObject' => get(BaseObject::class)
        ];
        ObjectFactory::setPreInit($arr);
        $defs = ObjectFactory::getDefinitions();
        $this->assertArrayHasKey('pre', $defs);
        $this->assertInstanceOf(BaseObject::class, ObjectFactory::get('baseObject'));
    }

    public function testInitAndGet()
    {
        $arr = [
            'baseObject' => get(BaseObject::class)
        ];
        ObjectFactory::setDefinitions($arr);
        ObjectFactory::init();
        $this->assertInstanceOf(BaseObject::class, ObjectFactory::get('baseObject'));
    }

    public function testCreateWithString()
    {
        $this->assertInstanceOf(BaseObject::class, ObjectFactory::createObject(BaseObject::class));
    }

    public function testConfigure()
    {
        $obj = new BaseObject();
        ObjectFactory::configure($obj, ['name' => 'test']);
        $this->assertEquals('test', $obj->name);
    }
}