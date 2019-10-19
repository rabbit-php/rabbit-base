<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/13
 * Time: 15:45
 */

namespace rabbit\handler;

use Psr\Http\Message\ResponseInterface;

/**
 * Interface ErrorHandlerInterface
 * @package rabbit\handler
 */
interface ErrorHandlerInterface
{
    /**
     * @param \Throwable $throw
     * @return mixed
     */
    public function handle(\Throwable $throw): ResponseInterface;
}
