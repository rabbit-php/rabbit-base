<?php
declare(strict_types=1);

namespace Rabbit\Base\Helper;


use Rabbit\Base\Contract\ArrayAble;

/**
 * Class JsonHelper
 * @package rabbit\helper
 */
class JsonHelper
{

    /**
     * @param $json
     * @param bool $assoc
     * @param int $depth
     * @param int $options
     * @return mixed
     */
    public static function decode($json, $assoc = false, $depth = 512, $options = 0)
    {
        $data = \json_decode($json, $assoc, $depth, $options);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException('json_decode error: ' . json_last_error_msg());
        }

        return $data;
    }

    /**
     * @param $value
     * @param int $options
     * @param int $depth
     * @return string
     */
    public static function encode($value, $options = 0, $depth = 512): string
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }
        $json = \json_encode($value, $options, $depth);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException('json_encode error: ' . json_last_error_msg());
        }

        return $json;
    }
}
