<?php

namespace rabbit\helper;

use rabbit\contract\Arrayable;

/**
 * Class ArrayHelper
 * @package rabbit\helper
 */
class ArrayHelper
{
    /**
     * @param $object
     * @param array $properties
     * @param bool $recursive
     * @return array
     */
    public static function toArray($object, $properties = [], $recursive = true): array
    {
        if (is_array($object)) {
            if ($recursive) {
                foreach ($object as $key => $value) {
                    if (is_array($value) || is_object($value)) {
                        $object[$key] = static::toArray($value, $properties, true);
                    }
                }
            }

            return $object;
        } elseif (is_object($object)) {
            if (!empty($properties)) {
                $className = get_class($object);
                if (!empty($properties[$className])) {
                    $result = [];
                    foreach ($properties[$className] as $key => $name) {
                        if (is_int($key)) {
                            $result[$name] = $object->$name;
                        } else {
                            $result[$key] = static::getValue($object, $name);
                        }
                    }

                    return $recursive ? static::toArray($result, $properties) : $result;
                }
            }
            if ($object instanceof Arrayable) {
                $result = $object->toArray([], [], $recursive);
            } else {
                $result = [];
                foreach ($object as $key => $value) {
                    $result[$key] = $value;
                }
            }

            return $recursive ? static::toArray($result, $properties) : $result;
        }

        return [$object];
    }

    /**
     * @param $a
     * @param $b
     * @return array
     */
    public static function merge($a, $b): array
    {
        $args = func_get_args();
        $res = array_shift($args);
        while (!empty($args)) {
            foreach (array_shift($args) as $k => $v) {
                if (is_int($k)) {
                    if (array_key_exists($k, $res)) {
                        $res[] = $v;
                    } else {
                        $res[$k] = $v;
                    }
                } elseif (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                    $res[$k] = self::merge($res[$k], $v);
                } else {
                    $res[$k] = $v;
                }
            }
        }

        return $res;
    }

    /**
     * @param $array
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public static function getValue($array, $key, $default = null)
    {
        if ($key instanceof \Closure) {
            return $key($array, $default);
        }

        if (is_array($key)) {
            $lastKey = array_pop($key);
            foreach ($key as $keyPart) {
                $array = static::getValue($array, $keyPart);
            }
            $key = $lastKey;
        }

        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            return $array[$key];
        }

        if (($pos = strrpos($key, '.')) !== false) {
            $array = static::getValue($array, substr($key, 0, $pos), $default);
            $key = substr($key, $pos + 1);
        }

        if (is_object($array)) {
            // this is expected to fail if the property does not exist, or __get() is not implemented
            // it is not reliably possible to check whether a property is accessible beforehand
            return $array->$key;
        } elseif (is_array($array)) {
            return (isset($array[$key]) || array_key_exists($key, $array)) ? $array[$key] : $default;
        }

        return $default;
    }

    /**
     * @param $array
     * @param $path
     * @param $value
     */
    public static function setValue(&$array, $path, $value): void
    {
        if ($path === null) {
            $array = $value;
            return;
        }

        $keys = is_array($path) ? $path : explode('.', $path);

        while (count($keys) > 1) {
            $key = array_shift($keys);
            if (!isset($array[$key])) {
                $array[$key] = [];
            }
            if (!is_array($array[$key])) {
                $array[$key] = [$array[$key]];
            }
            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * @param $array
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    public static function remove(&$array, $key, $default = null)
    {
        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            $value = $array[$key];
            unset($array[$key]);

            return $value;
        }

        return $default;
    }

    /**
     * @param $array
     * @param $value
     * @return array
     */
    public static function removeValue(&$array, $value): array
    {
        $result = [];
        if (is_array($array)) {
            foreach ($array as $key => $val) {
                if ($val === $value) {
                    $result[$key] = $val;
                    unset($array[$key]);
                }
            }
        }

        return $result;
    }

    /**
     * @param $array
     * @param $key
     * @param array $groups
     * @return array
     */
    public static function index($array, $key, $groups = []): array
    {
        $result = [];
        $groups = (array)$groups;

        foreach ($array as $element) {
            $lastArray = &$result;

            foreach ($groups as $group) {
                $value = static::getValue($element, $group);
                if (!array_key_exists($value, $lastArray)) {
                    $lastArray[$value] = [];
                }
                $lastArray = &$lastArray[$value];
            }

            if ($key === null) {
                if (!empty($groups)) {
                    $lastArray[] = $element;
                }
            } else {
                $value = static::getValue($element, $key);
                if ($value !== null) {
                    if (is_float($value)) {
                        $value = (string)$value;
                    }
                    $lastArray[$value] = $element;
                }
            }
            unset($lastArray);
        }

        return $result;
    }

    /**
     * @param $array
     * @param $name
     * @param bool $keepKeys
     * @return array
     */
    public static function getColumn($array, $name, $keepKeys = true): array
    {
        $result = [];
        if ($keepKeys) {
            foreach ($array as $k => $element) {
                $result[$k] = static::getValue($element, $name);
            }
        } else {
            foreach ($array as $element) {
                $result[] = static::getValue($element, $name);
            }
        }

        return $result;
    }

    /**
     * @param $array
     * @param $from
     * @param $to
     * @param null $group
     * @return array
     */
    public static function map($array, $from, $to, $group = null): array
    {
        $result = [];
        foreach ($array as $element) {
            $key = static::getValue($element, $from);
            $value = static::getValue($element, $to);
            if ($group !== null) {
                $result[static::getValue($element, $group)][$key] = $value;
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    /**
     * @param $key
     * @param $array
     * @param bool $caseSensitive
     * @return bool
     */
    public static function keyExists($key, $array, $caseSensitive = true): bool
    {
        if ($caseSensitive) {
            // Function `isset` checks key faster but skips `null`, `array_key_exists` handles this case
            // http://php.net/manual/en/function.array-key-exists.php#107786
            return isset($array[$key]) || array_key_exists($key, $array);
        }

        foreach (array_keys($array) as $k) {
            if (strcasecmp($key, $k) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $array
     * @param $key
     * @param int $direction
     * @param int $sortFlag
     */
    public static function multisort(&$array, $key, $direction = SORT_ASC, $sortFlag = SORT_REGULAR): void
    {
        $keys = is_array($key) ? $key : [$key];
        if (empty($keys) || empty($array)) {
            return;
        }
        $n = count($keys);
        if (is_scalar($direction)) {
            $direction = array_fill(0, $n, $direction);
        } elseif (count($direction) !== $n) {
            throw new \InvalidArgumentException('The length of $direction parameter must be the same as that of $keys.');
        }
        if (is_scalar($sortFlag)) {
            $sortFlag = array_fill(0, $n, $sortFlag);
        } elseif (count($sortFlag) !== $n) {
            throw new \InvalidArgumentException('The length of $sortFlag parameter must be the same as that of $keys.');
        }
        $args = [];
        foreach ($keys as $i => $key) {
            $flag = $sortFlag[$i];
            $args[] = static::getColumn($array, $key);
            $args[] = $direction[$i];
            $args[] = $flag;
        }

        // This fix is used for cases when main sorting specified by columns has equal values
        // Without it it will lead to Fatal Error: Nesting level too deep - recursive dependency?
        $args[] = range(1, count($array));
        $args[] = SORT_ASC;
        $args[] = SORT_NUMERIC;

        $args[] = &$array;
        call_user_func_array('array_multisort', $args);
    }

    /**
     * @param $array
     * @param bool $allStrings
     * @return bool
     */
    public static function isAssociative($array, $allStrings = true): bool
    {
        if (!is_array($array) || empty($array)) {
            return false;
        }

        if ($allStrings) {
            foreach ($array as $key => $value) {
                if (!is_string($key)) {
                    return false;
                }
            }

            return true;
        }

        foreach ($array as $key => $value) {
            if (is_string($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $array
     * @param bool $consecutive
     * @return bool
     */
    public static function isIndexed($array, $consecutive = false): bool
    {
        if (!is_array($array)) {
            return false;
        }

        if (empty($array)) {
            return true;
        }

        if ($consecutive) {
            return array_keys($array) === range(0, count($array) - 1);
        }

        foreach ($array as $key => $value) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $needle
     * @param $haystack
     * @param bool $strict
     * @return bool
     */
    public static function isIn($needle, $haystack, $strict = false): bool
    {
        if ($haystack instanceof \Traversable) {
            foreach ($haystack as $value) {
                if ($needle == $value && (!$strict || $needle === $value)) {
                    return true;
                }
            }
        } elseif (is_array($haystack)) {
            return in_array($needle, $haystack, $strict);
        } else {
            throw new \InvalidArgumentException('Argument $haystack must be an array or implement Traversable');
        }

        return false;
    }

    /**
     * @param $var
     * @return bool
     */
    public static function isTraversable($var): bool
    {
        return is_array($var) || $var instanceof \Traversable;
    }

    /**
     * @param $needles
     * @param $haystack
     * @param bool $strict
     * @return bool
     */
    public static function isSubset($needles, $haystack, $strict = false): bool
    {
        if (is_array($needles) || $needles instanceof \Traversable) {
            foreach ($needles as $needle) {
                if (!static::isIn($needle, $haystack, $strict)) {
                    return false;
                }
            }

            return true;
        }

        throw new \InvalidArgumentException('Argument $needles must be an array or implement Traversable');
    }

    /**
     * @param $array
     * @param $filters
     * @return array
     */
    public static function filter($array, $filters): array
    {
        $result = [];
        $forbiddenVars = [];

        foreach ($filters as $var) {
            $keys = explode('.', $var);
            $globalKey = $keys[0];
            $localKey = isset($keys[1]) ? $keys[1] : null;

            if ($globalKey[0] === '!') {
                $forbiddenVars[] = [
                    substr($globalKey, 1),
                    $localKey,
                ];
                continue;
            }

            if (!array_key_exists($globalKey, $array)) {
                continue;
            }
            if ($localKey === null) {
                $result[$globalKey] = $array[$globalKey];
                continue;
            }
            if (!isset($array[$globalKey][$localKey])) {
                continue;
            }
            if (!array_key_exists($globalKey, $result)) {
                $result[$globalKey] = [];
            }
            $result[$globalKey][$localKey] = $array[$globalKey][$localKey];
        }

        foreach ($forbiddenVars as $var) {
            list($globalKey, $localKey) = $var;
            if (array_key_exists($globalKey, $result)) {
                unset($result[$globalKey][$localKey]);
            }
        }

        return $result;
    }

    /**
     * @param array $array
     * @param array $keys
     * @param null $default
     * @return array|null
     */
    public static function getValueByList(array $array, array $keys, $default = null): ?array
    {
        if (!is_array($array) || !is_array($keys)) {
            return null;
        }
        $result = [];

        foreach ($keys as $index => $key) {
            if (is_array($default)) {
                $result[$key] = $default[$index];
            } else {
                $result[$key] = $default;
            }
            foreach ($array as $value) {
                if (static::keyExists($key, $value, false)) {
                    $result[$key][] = $value[$key];
                }
            }
        }
        return $result;
    }

    /**
     * @param array $array
     * @param array $keys
     * @param null $default
     * @return array|null
     */
    public static function getValueByArray(array $array, array $keys, $default = null): ?array
    {
        if (!is_array($array) || !is_array($keys)) {
            return null;
        }
        $result = [];

        foreach ($keys as $index => $key) {
            if (is_array($default)) {
                $result[$key] = $default[$index];
            } else {
                $result[$key] = $default;
            }
            foreach ($array as $akey => $value) {
                if ($akey === $key) {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }

    /**
     * @param array $array
     * @param $key
     * @param $group
     * @return array|null
     */
    public static function sum(array $array, $key, $group): ?array
    {
        if (!is_array($array) || !$key || !$group) {
            return null;
        }
        $result = [];
        foreach ($array as $index => $value) {
            if (in_array($value[$group], array_keys($result))) {
                $result[$group] += $value[$key];
            } else {
                $result[$value[$group]] = $value[$key];
            }
        }
        return $result;
    }

}
