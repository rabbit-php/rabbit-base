<?php
declare(strict_types=1);

namespace Rabbit\Base\Helper;

use DOMDocument;
use DOMElement;
use DOMText;
use Rabbit\Base\Contract\ArrayAble;
use Traversable;

/**
 * Class XmlHelper
 * @package Rabbit\Base\Helper
 */
class XmlHelper
{
    /**
     * @param string $xml
     * @return array
     */
    public static function decode(string $xml): array
    {
        return self::xmlToArray($xml);
    }

    /**
     * @param string $xml
     * @return array
     */
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

    /**
     * @param $data
     * @return array
     */
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

    /**
     * @param array $data
     * @return string
     */
    public static function encode(array $data): string
    {
        $xml = '<xml>';
        $xml .= self::arrayToXml($data);
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * @param array $data
     * @return string
     */
    public static function arrayToXml(array $data): string
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

    /**
     * @param string $string
     * @return string
     */
    protected static function characterDataReplace(string $string): string
    {
        return sprintf('<![CDATA[%s]]>', $string);
    }


    /**
     * @param array $data
     * @param string $rootTag
     * @param string $itemTag
     * @param string $version
     * @param string $encoding
     * @return mixed
     */
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

    /**
     * @param DOMElement $element
     * @param mixed $data
     * @param string $itemTag
     * @return void
     */
    protected static function buildXml(DOMElement $element, $data, string $itemTag = 'item'): void
    {
        if (is_array($data) ||
            ($data instanceof Traversable && !$data instanceof Arrayable)
        ) {
            if ($data) {
                foreach ($data as $name => $value) {
                    if (is_int($name) && is_object($value)) {
                        self::buildXml($element, $value);
                    } elseif (is_array($value) || is_object($value)) {
                        $child = new DOMElement(is_int($name) ? $itemTag : $name);
                        $element->appendChild($child);
                        self::buildXml($child, $value);
                    } else {
                        $child = new DOMElement(is_int($name) ? $itemTag : $name);
                        $element->appendChild($child);
                        $child->appendChild(new DOMText((string)$value));
                    }
                }
            } else {
                $element->appendChild(new DOMText((string)null));
            }

        } elseif (is_object($data)) {
            $child = new DOMElement(StringHelper::basename(get_class($data)));
            $element->appendChild($child);
            if ($data instanceof Arrayable) {
                self::buildXml($child, $data->toArray());
            } else {
                $array = [];
                foreach ($data as $name => $value) {
                    $array[$name] = $value;
                }
                self::buildXml($child, $array);
            }
        } else {
            $element->appendChild(new DOMText((string)$data));
        }
    }
}
