<?php

declare(strict_types=1);

namespace Rabbit\Base\Helper;

use SplFixedArray;

class SplArrayHelper
{
    public static function push(SplFixedArray $array, SplFixedArray $array1, int $offset = 0, int $length = null): void
    {
        $maxLen = Min($array->count() - $offset, $array1->count());
        if ($length === null || $maxLen < $length) {
            $length = $maxLen;
        }
        for ($i = 0; $i < $length; $i++) {
            $array[$i + $offset] = $array1[$i];
        }
    }

    public static function merge(SplFixedArray $array, ...$array1): SplFixedArray
    {
        $index = 0;
        /** @var SplFixedArray $spl */
        foreach ($array1 as $spl) {
            foreach ($spl as $value) {
                $array[$index] = $value;
                $index++;
            }
        }
        return $array;
    }

    public static function splice(SplFixedArray $array, int $offset, int $length = null): SplFixedArray
    {
        $maxLen = $array->count() - $offset;
        if ($length === null || $maxLen < $length) {
            $length = $maxLen;
        }
        $result = new SplFixedArray($length);
        for ($i = $offset; $i < $offset + $length; $i++) {
            $result[$i - $offset] = $array[$i];
        }
        return $result;
    }
}
