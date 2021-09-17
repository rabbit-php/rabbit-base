<?php

declare(strict_types=1);

namespace Rabbit\Base\Helper;

use Rabbit\Base\Contract\ArrayAble;

class JsonHelper
{
    public static function decode(string $json, ?bool $assoc = false, int $depth = 512, int $options = 0)
    {
        $data = \json_decode($json, $assoc, $depth, $options);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \InvalidArgumentException('json_decode error: ' . json_last_error_msg());
        }

        return $data;
    }

    public static function encode(Arrayable|array $value, int $options = 0, int $depth = 512): string
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
