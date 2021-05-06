<?php

declare(strict_types=1);

namespace Rabbit\Base\Helper;

/**
 * Class UrlHelper
 * @package Rabbit\Base\Helper
 */
class UrlHelper
{
    /**
     * @param array $parsed_url
     * @param bool $withAuth
     * @return string
     */
    public static function unParseUrl(array $parsed_url, bool $withAuth = true, string $withSchema = null): string
    {
        $scheme = $withSchema !== null ? "$withSchema://" : (isset($parsed_url['scheme']) ? "{$parsed_url['scheme']}://" : '');
        $host = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user = isset($parsed_url['user']) && $withAuth ? $parsed_url['user'] : '';
        $pass = isset($parsed_url['pass']) && $withAuth ? ':' . $parsed_url['pass'] : '';
        $pass = ($user || $pass) && $withAuth ? "$pass@" : '';
        $path = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    public static function unParseUrlArray(array $parsed_url): array
    {
        return array_filter([
            'scheme' => isset($parsed_url['scheme']) ? $parsed_url['scheme'] : null,
            'host' => isset($parsed_url['host']) ? $parsed_url['host'] : null,
            'port' => isset($parsed_url['port']) ? $parsed_url['port'] : null,
            'user' => isset($parsed_url['user']) ? urldecode($parsed_url['user']) : null,
            'pass' => isset($parsed_url['pass']) ? urldecode($parsed_url['pass']) : null,
            'path' => isset($parsed_url['path']) ? urldecode($parsed_url['path']) : null,
            'query' => isset($parsed_url['query']) ? urldecode($parsed_url['query']) : null,
            'fragment' => isset($parsed_url['fragment']) ? $parsed_url['fragment'] : null
        ]);
    }

    /**
     * @param array $uris
     * @return array
     */
    public static function dns2IP(array $uris): array
    {
        $ips = [];
        foreach ($uris as $uri) {
            $url = parse_url($uri);
            if (!isset($url['host'])) {
                continue;
            }
            if (filter_var($url['host'], FILTER_VALIDATE_IP)) {
                $ips[] = $uri;
                continue;
            }
            $res = gethostbynamel($url['host']);
            if ($res) {
                foreach ($res as $ip) {
                    $url['host'] = $ip;
                    $ips[] = self::unParseUrl($url);
                }
            } else {
                $ips[] = $uri;
            }
        }
        return $ips;
    }
}
