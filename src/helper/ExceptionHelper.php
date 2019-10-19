<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/21
 * Time: 2:37
 */

namespace rabbit\helper;

use rabbit\core\Exception;

/**
 * Class ExceptionHelper
 * @package rabbit\helper
 */
class ExceptionHelper
{
    /**
     * @param \Throwable $exception
     * @return array
     * @throws \Exception
     */
    public static function convertExceptionToArray(\Throwable $exception): array
    {
        $trace = explode("\n", $exception->getTraceAsString());
        $count = count($trace);
        $depth = getDI('debug_depth', false, 0);
        $count < $depth && $depth = $count;
        $array = [
            'name' => $exception instanceof Exception ? $exception->getName() : 'Exception',
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'type' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack-trace' => $depth === 0 ? $depth : array_slice($trace, 0, $depth),
        ];
        if (($prev = $exception->getPrevious()) !== null) {
            $array['previous'] = self::convertExceptionToArray($prev);
        }

        return $array;
    }
}
