<?php
declare(strict_types=1);

namespace Rabbit\Base\Helper;

use Rabbit\Base\Core\Exception;
use Throwable;

/**
 * Class ExceptionHelper
 * @package Rabbit\Base\Helper
 */
class ExceptionHelper
{
    /**
     * @param Throwable $exception
     * @return array
     * @throws Throwable
     */
    public static function convertExceptionToArray(Throwable $exception): array
    {
        $trace = explode("\n", $exception->getTraceAsString());
        $count = count($trace);
        $depth = getDI('debug_depth', false, 3);
        $count < $depth && $depth = $count;
        $array = [
            'name' => $exception instanceof Exception ? $exception->getName() : 'Exception',
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'type' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack-trace' => $depth === 0 ? $trace : array_slice($trace, 0, $depth),
        ];
        if (($prev = $exception->getPrevious()) !== null) {
            $array['previous'] = self::convertExceptionToArray($prev);
        }

        return $array;
    }

    /**
     * @param Throwable $exception
     * @return string
     * @throws Throwable
     */
    public static function dumpExceptionToString(Throwable $exception): string
    {
        return VarDumper::getDumper()->dumpAsString(static::convertExceptionToArray($exception));
    }
}
