<?php

declare(strict_types=1);

namespace Rabbit\Base\Core;

/**
 * Class Context
 * @package Rabbit\Base\Core
 */
class Context
{
    public static function set(string $name, $value, string $key = null): void
    {
        $context = getContext();
        if ($key !== null) {
            $context[$key][$name] = $value;
        } else {
            $context[$name] = $value;
        }
    }

    public static function get(string $name, string $key = null): mixed
    {
        $context = getContext();
        if ($key !== null) {
            if (!isset($context[$key])) {
                return null;
            } else {
                return isset($context[$key][$name]) ? $context[$key][$name] : null;
            }
        }
        return isset($context[$name]) ? $context[$name] : null;
    }

    public static function has(string $name, string $key = null): bool
    {
        $context = getContext();
        if ($key !== null) {
            return isset($context[$key]) && isset($context[$key][$name]);
        }
        return isset($context[$name]);
    }

    public static function delete(string $name, string $key = null): mixed
    {
        $ret = null;
        $context = getContext();
        if ($key !== null && isset($context[$key]) && isset($context[$key][$name])) {
            $ret = $context[$key][$name];
            unset($context[$key][$name]);
        } elseif (isset($context[$name])) {
            $ret = $context[$name];
            unset($context[$name]);
        }
        return $ret;
    }
}
