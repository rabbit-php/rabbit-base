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
     * @param $exception
     * @return array
     */
    public static function convertExceptionToArray($exception): array
    {
        $array = [
            'name' => $exception instanceof Exception ? $exception->getName() : 'Exception',
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'type' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'stack-trace' => explode("\n", $exception->getTraceAsString()),
        ];
        if (($prev = $exception->getPrevious()) !== null) {
            $array['previous'] = self::convertExceptionToArray($prev);
        }

        return $array;
    }
}