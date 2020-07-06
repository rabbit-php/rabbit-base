<?php
declare(strict_types=1);

namespace Rabbit\Base\Helper;

/**
 * Class Inflector
 * @package Rabbit\Base\Helper
 */
class Inflector
{
    /**
     * @param $name
     * @param string $separator
     * @param bool $strict
     * @return mixed|null|string|string[]
     */
    public static function camel2id($name, $separator = '-', $strict = false)
    {
        $regex = $strict ? '/\p{Lu}/u' : '/(?<!\p{Lu})\p{Lu}/u';
        if ($separator === '_') {
            return mb_strtolower(trim(preg_replace($regex, '_\0', $name), '_'));
        }

        return mb_strtolower(trim(
            str_replace('_', $separator, preg_replace($regex, $separator . '\0', $name)),
            $separator
        ));
    }

    /**
     * @param $name
     * @param bool $ucwords
     * @return string
     */
    public static function camel2words($name, $ucwords = true): string
    {
        $label = mb_strtolower(trim(str_replace([
            '-',
            '_',
            '.',
        ], ' ', preg_replace('/(\p{Lu})/u', ' \0', $name))));

        return $ucwords ? StringHelper::mb_ucwords($label) : $label;
    }
}
