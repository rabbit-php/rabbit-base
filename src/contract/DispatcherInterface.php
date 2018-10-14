<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/10/9
 * Time: 11:56
 */

namespace rabbit\contract;


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface DispatcherInterface
{
    /**
     * Dispatch
     *
     * @param array ...$params dispatcher params
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface;
}