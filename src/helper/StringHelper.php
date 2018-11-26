<?php

namespace rabbit\helper;

/**
 * Class StringHelper
 * @package rabbit\helper
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

    /**
     * @param string $str
     * @param string $find
     * @param int $n
     * @return int
     */
    public static function str_n_pos(string $str, string $find, int $n): int
    {
        $pos_val = 0;
        for ($i = 1; $i <= $n; $i++) {
            $pos = strpos($str, $find);
            $str = substr($str, $pos + 1);
            $pos_val = $pos + $pos_val + 1;
        }
        return $pos_val - 1;
    }
}
