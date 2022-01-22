<?php

declare(strict_types=1);

namespace Rabbit\Base\Helper;

use Rabbit\Base\Contract\ArrayAble;
use Rabbit\HttpServer\Exceptions\BadRequestHttpException;

class JsonHelper
{
    public static function decode(string $json, ?bool $assoc = false, int $depth = 512, int $options = 0)
    {
        if (extension_loaded('simdjson') && null === $data = \simdjson_decode($json, $assoc, $depth)) {
            throw new BadRequestHttpException('json error');
        } else {
            $data =  \json_decode($json, $assoc, $depth, $options | JSON_THROW_ON_ERROR);
        }
        return $data;
    }

    public static function encode(Arrayable|array $value, int $options = 0, int $depth = 512): string
    {
        if ($value instanceof Arrayable) {
            $value = $value->toArray();
        }
        $json = \json_encode($value, $options | JSON_THROW_ON_ERROR, $depth);
        return $json;
    }

    public static function valid(string $json): bool
    {
        if (extension_loaded('simdjson')) {
            return \simdjson_is_valid($json);
        }
        \json_decode($json);
        return JSON_ERROR_NONE === json_last_error();
    }
}
