<?php
declare(strict_types=1);

namespace Rabbit\Base\Tests\Core;


use Rabbit\Base\Core\BaseObject;
use Rabbit\Base\Tests\TestCase;

/**
 * Class BaseObjectTest
 * @package Rabbit\Base\Tests\Core
 */
class BaseObjectTest extends TestCase
{
    public function testGet()
    {
        $obj = new BaseObject();
        $this->expectExceptionMessage('Undefined property: name');
        $obj->name;
    }

    public function testSet()
    {
        $obj = new BaseObject();
        $obj->name = 'name';
        $this->assertEquals('name', $obj->name);
    }
}