<?php
declare(strict_types=1);

namespace Rabbit\Base\Tests\Core;

use Rabbit\Base\Core\Context;
use Rabbit\Base\Tests\TestCase;
use function Co\Run;

/**
 * Class ContextTest
 * @package Rabbit\Base\Tests\Core
 */
class ContextTest extends TestCase
{
    public function testGet()
    {
        Run(function () {
            Context::set('key', 'value');
            $this->assertEquals('value', Context::get('key'));
        });
    }

    public function testHas()
    {
        Run(function () {
            Context::set('key', 'value');
            $this->assertTrue(Context::has('key'));
        });
    }

    public function testNotHas()
    {
        Run(function () {
            $this->assertFalse(Context::has('key'));
        });
    }

    public function testDelete()
    {
        Run(function () {
            Context::set('key', 'value');
            $this->assertEquals('value', Context::get('key'));
            Context::delete('key');
            $this->assertFalse(Context::has('key'));
        });
    }
}