<?php

namespace rabbit\helpers;

/**
 * Class StringHelper
 * @package rabbit\helpers
 */
class StringHelper
{
    /**
     * @param $number
     * @return mixed
     */
    public static function floatToString($number): string
    {
        // . and , are the only decimal separators known in ICU data,
        // so its safe to call str_replace here
        return str_replace(',', '.', (string)$number);
    }
}
