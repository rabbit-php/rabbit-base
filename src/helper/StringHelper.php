<?php

namespace rabbit\helpers;

class StringHelper
{
    /**
     * Safely casts a float to string independent of the current locale.
     *
     * The decimal separator will always be `.`.
     * @param float|int $number a floating point number or integer.
     * @return string the string representation of the number.
     */
    public static function floatToString($number)
    {
        // . and , are the only decimal separators known in ICU data,
        // so its safe to call str_replace here
        return str_replace(',', '.', (string) $number);
    }
}
