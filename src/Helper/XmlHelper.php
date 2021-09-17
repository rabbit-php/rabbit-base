<?php

declare(strict_types=1);

namespace Rabbit\Base\Helper;

use DOMDocument;
use DOMElement;
use DOMException;
use DOMText;
use Rabbit\Base\Contract\ArrayAble;
use Traversable;

/**
 * Class XmlHelper
 * @package Rabbit\Base\Helper
 */
class XmlHelper
{
    public static function decode(string $xml): array
    {
        return self::xmlToArray($xml);
    }

    public static function xmlToArray(string $xml): array
    {
        $res = [];
        //如果为空,一般是xml有空格之类的,导致解析失败
        $data = @(array)simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);
        if (isset($data[0]) && $data[0] === false) {
            $data = null;
        }
        if ($data) {
            $res = self::parseToArray($data);
        }
        return $res;
    }

    protected static function parseToArray($data): array
    {
        $res = null;
        if (is_object($data)) {
            $data = (array)$data;
        }
        if (is_array($data)) {
            foreach ($data as $key => $val) {
                if (is_iterable($val)) {
                    $res[$key] = self::parseToArray($val);
                } else {
                    $res[$key] = $val;
                }
            }
        }
        return $res;
    }

    public static function encode(array $data): string
    {
        $xml = '<xml>';
        $xml .= self::arrayToXml($data);
        $xml .= '</xml>';
        return $xml;
    }

    public static function arrayToXml(array|iterable $data): string
    {
        $xml = '';
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                $xml .= "<$key>";
                if (is_iterable($val)) {
                    $xml .= self::arrayToXml($val);
                } elseif (is_numeric($val)) {
                    $xml .= $val;
                } else {
                    $xml .= self::characterDataReplace($val);
                }
                $xml .= "</$key>";
            }
        }
        return $xml;
    }

    protected static function characterDataReplace(string $string): string
    {
        return sprintf('<![CDATA[%s]]>', $string);
    }

    public static function format(?array $data, string $rootTag = 'root', string $itemTag = 'item', string $version = '1.0', string $encoding = 'UTF-8'): string
    {
        $content = '';
        if ($data !== null) {
            $dom = new DOMDocument($version, $encoding);
            $root = new DOMElement($rootTag);
            $dom->appendChild($root);
            self::buildXml($root, $data, $itemTag);
            $content = $dom->saveXML();
        }

        return $content;
    }

    protected static function buildXml(DOMElement $element, $data, string $itemTag): void
    {
        if (
            is_array($data) ||
            ($data instanceof Traversable && !$data instanceof Arrayable)
        ) {
            foreach ($data as $name => $value) {
                if (is_int($name) && is_object($value)) {
                    self::buildXml($element, $value, $itemTag);
                } elseif (is_array($value) || is_object($value)) {
                    $child = new DOMElement(self::getValidXmlElementName($name, $itemTag));
                    $element->appendChild($child);
                    self::buildXml($child, $value, $itemTag);
                } else {
                    $child = new DOMElement(self::getValidXmlElementName($name, $itemTag));
                    $element->appendChild($child);
                    $child->appendChild(new DOMText(self::formatScalarValue($value)));
                }
            }
        } elseif (is_object($data)) {
            $child = $element;
            if ($data instanceof Arrayable) {
                self::buildXml($child, $data->toArray(), $itemTag);
            } else {
                $array = [];
                foreach ($data as $name => $value) {
                    $array[$name] = $value;
                }
                self::buildXml($child, $array, $itemTag);
            }
        } else {
            $element->appendChild(new DOMText(self::formatScalarValue($data)));
        }
    }

    protected static function getValidXmlElementName(int|string|bool $name, string $itemTag): string
    {
        if (empty($name) || is_int($name) || !self::isValidXmlName($name)) {
            return $itemTag;
        }

        return $name;
    }

    protected static function isValidXmlName(int|string|bool $name): bool
    {
        try {
            new DOMElement($name);
            return true;
        } catch (DOMException $e) {
            throw $e;
        }
    }

    protected static function formatScalarValue(int|string|bool $value): string
    {
        if ($value === true) {
            return 'true';
        }
        if ($value === false) {
            return 'false';
        }
        if (is_float($value)) {
            return StringHelper::floatToString($value);
        }
        return (string)$value;
    }
}
