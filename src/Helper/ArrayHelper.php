<?php

namespace Rabbit\Base\Helper;

use Closure;
use Rabbit\Base\Contract\ArrayAble;
use Traversable;

class ArrayHelper
{
    public static function toArrayJson(array &$array): void
    {
        foreach ($array as &$value) {
            if (is_string($value)) {
                $json = json_decode($value, true);
                $value = json_last_error() === JSON_ERROR_NONE ? $json : $value;
                if (is_array($value)) {
                    self::toArrayJson($value);
                }
            } elseif (is_array($value)) {
                self::toArrayJson($value);
            }
        }
    }

    public static function toArray(mixed $object, array $properties = [], bool $recursive = true): array
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
            if ($object instanceof ArrayAble) {
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

    public static function getValue(array|object $array, array|Closure|string $key, mixed $default = null): mixed
    {
        if ($key instanceof Closure) {
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

    public static function getOneValue(array &$array, array $keys, mixed $defualt = null, bool $remove = false): mixed
    {
        $result = $defualt;
        foreach ($keys as $key) {
            if (isset($array[$key]) && $result === $defualt) {
                if ($remove) {
                    $result = $array[$key];
                    unset($array[$key]);
                } else {
                    return $array[$key];
                }
            }
        }
        return $result;
    }

    public static function merge(mixed $a, mixed $b): array
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

    public static function setValue(array &$array, null|array|int|string $path, mixed $value): void
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

    public static function remove(array &$array, string $key, mixed $default = null): mixed
    {
        if (is_array($array) && (isset($array[$key]) || array_key_exists($key, $array))) {
            $value = $array[$key];
            unset($array[$key]);

            return $value;
        }

        return $default;
    }

    public static function removeKeys(array &$array, array $keys, mixed $default = null): void
    {
        $result = [];
        foreach ($keys as $index => $key) {
            $result[$key] = self::remove($array, $key, is_array($default) ? $default[$index] : $default);
        }
    }

    public static function removeValue(array &$array, mixed $value): array
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

    public static function index(array $array, ?string $key, array $groups = []): array
    {
        $result = [];

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

    public static function map(array $array, string $from, string $to, null|array|Closure|string $group = null): array
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

    public static function multisort(array &$array, array|int|string $key, int $direction = SORT_ASC, int $sortFlag = SORT_REGULAR): void
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

    public static function getColumn(iterable $array, string|int|Closure $name, bool $keepKeys = true): array
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

    public static function isIndexed(array $array, bool $isList = false): bool
    {
        if (empty($array)) {
            return true;
        }

        if (!$isList) {
            return is_int(array_key_first($array));
        }

        return array_is_list($array);
    }

    public static function isTraversable(mixed $var): bool
    {
        return is_array($var) || $var instanceof Traversable;
    }

    public static function isSubset(array|Traversable $needles, array|Traversable $haystack, bool $strict = false): bool
    {
        foreach ($needles as $needle) {
            if (!static::isIn($needle, $haystack, $strict)) {
                return false;
            }
        }

        return true;
    }

    public static function isIn(mixed $needle, array|Traversable $haystack, bool $strict = false): bool
    {
        if ($haystack instanceof Traversable) {
            foreach ($haystack as $value) {
                if ($needle == $value && (!$strict || $needle === $value)) {
                    return true;
                }
            }
        } elseif (is_array($haystack)) {
            return in_array($needle, $haystack, $strict);
        }

        return false;
    }

    public static function filter(array $array, array $filters): array
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

    public static function getValueByArray(
        array $array,
        array $keys,
        array $default = null,
        array $newKeys = null
    ): ?array {
        if (($newKeys && is_array($newKeys) && count($keys) !== count($newKeys)) ||
            (is_array($default) && self::isIndexed($default) && count($keys) !== count($default))
        ) {
            return $default;
        }
        $result = [];

        foreach ($keys as $index => $key) {
            $newKey = $newKeys ? $newKeys[$index] : (is_array($newKeys) ? $key : $index);
            if (is_array($default)) {
                $result[$newKey] = isset($default[$key]) ? $default[$key] : (isset($default[$index]) ? $default[$index] : null);
            } else {
                $result[$newKey] = $default;
            }
            foreach ($array as $akey => $value) {
                if ($akey === $key) {
                    $result[$newKey] = $value;
                }
            }
        }
        return $result;
    }

    public static function getValueByList(
        array $array,
        array $keys,
        array $default = null,
        array $newKeys = null
    ): ?array {
        if (!is_array($array) || !is_array($keys) || !static::isIndexed($array)) {
            return null;
        }
        $result = [];
        foreach ($array as $index => $value) {
            $result[$index] = ArrayHelper::getValueByArray($array, $keys, $default, $newKeys);
        }
        return $result;
    }

    public static function keyExists(string|int $key, array $array, bool $caseSensitive = true): bool
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

    public static function sum(array $array, string|int $key, string|int $group): ?array
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

    public static function getObjectVars(object $object): ?array
    {
        return get_object_vars($object);
    }

    public static function toTree(array $list, string $pk = 'id', string $pid = 'pid', string $child = 'children', int $root = 0, bool $withKey = false): array
    {
        $tree = [];
        $refer = [];
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            $parentId = $data[$pid];
            if ($root === $parentId) {
                if ($withKey) {
                    $tree[$data[$pk]] = &$list[$key];
                } else {
                    $tree[] = &$list[$key];
                }
            } else {
                if ($refer[$parentId] ?? false) {
                    $parent = &$refer[$parentId];
                    $parent[$child][$data[$pk]] = &$list[$key];

                    $parent[$child] = array_values($parent[$child]);
                }
            }
        }
        return $tree;
    }
}
