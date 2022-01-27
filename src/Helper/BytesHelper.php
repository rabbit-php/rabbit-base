<?php

namespace Rabbit\Base\Helper;

use SplFixedArray;

/**
 * Class BytesHelper
 * @package rabbit\helper\kcp
 */
class BytesHelper
{
    const INT8 = 1;
    const INT16 = 2;
    const INT32 = 4;
    const INT64 = 8;

    const HEX = 1;
    const DEC = 0;

    /**
     * @param SplFixedArray $bytes
     * @param int $type
     * @return string
     */
    public static function bytes2string(SplFixedArray $bytes, int $type = self::DEC): string
    {
        $decFun = function () use ($bytes): string {
            $str = '';
            foreach ($bytes as $byte) {
                $str .= chr((int)$byte);
            }
            return $str;
        };
        switch ($type) {
            case self::HEX:
                $hex = implode("", $bytes->toArray());
                return pack("H*", $hex);
            default:
                return $decFun();
        }
    }

    /**
     * @param string $str
     * @param int $type
     * @return SplFixedArray
     */
    public static function string2bytes(string $str, int $type = self::DEC): SplFixedArray
    {
        switch ($type) {
            case self::DEC:
                $func = 'ord';
                break;
            case self::HEX:
                $func = 'bin2hex';
                break;
            default:
                $func = 'ord';
        }
        $bytes = new SplFixedArray(strlen($str));
        for ($i = 0; $i < strlen($str); $i++) {
            $bytes[$i] = $func($str[$i]);
        }
        return $bytes;
    }

    /**
     * @param SplFixedArray $bytes
     * @param int $position
     * @param int $bit
     * @return int
     */
    public static function bytesToUInt(SplFixedArray $bytes, int $position, int $bit): int
    {
        $val = 0;
        for ($i = ($bit - 1); $i >= 0; $i--) {
            if ($i === 0) {
                $val |= $bytes[$position + $i] & 0xff;
            } else {
                $val |= $bytes[$position + $i] & 0xff;
                $val <<= 8;
            }
        }

        return $val;
    }

    /**
     * @param int $val
     * @param int $bit
     * @return SplFixedArray
     */
    public static function uintToBytes(int $val, int $bit): SplFixedArray
    {
        $byte = new SplFixedArray($bit);
        for ($i = 0; $i < $byte->count(); $i++) {
            $byte[$i] = ($val >> ($i * 8) & 0xff);
        }
        return $byte;
    }
}
