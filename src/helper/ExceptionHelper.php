<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/21
 * Time: 2:37
 */

namespace rabbit\helper;
use rabbit\core\Exception;
use rabbit\core\ObjectFactory;
use rabbit\core\UserException;

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
        ];
        if (ObjectFactory::get('debug', false)) {
            $array['type'] = get_class($exception);
            if (!$exception instanceof UserException) {
                $array['file'] = $exception->getFile();
                $array['line'] = $exception->getLine();
                $array['stack-trace'] = explode("\n", $exception->getTraceAsString());
            }
        }
        if (($prev = $exception->getPrevious()) !== null) {
            $array['previous'] = self::convertExceptionToArray($prev);
        }

        return $array;
    }
}