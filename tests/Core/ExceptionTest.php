<?php
declare(strict_types=1);

namespace Rabbit\Base\Tests\Core;

use Rabbit\Base\Core\Exception;
use Rabbit\Base\Tests\TestCase;

/**
 * Class testException
 * @package Rabbit\Base\Tests\Core
 */
class ExceptionTest extends TestCase
{
    public function testException()
    {
        $this->expectException('Rabbit\Base\Core\Exception');
        throw new Exception();
    }
}