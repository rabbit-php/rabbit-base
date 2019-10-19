<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/11/21
 * Time: 2:37
 */

namespace rabbit\helper;

use rabbit\core\Exception;
use rabbit\exception\InvalidArgumentException;

/**
 * Class BaseFileHelper
 * @package rabbit\helper
 */
class FileHelper
{
    const PATTERN_NODIR = 1;
    const PATTERN_ENDSWITH = 4;
    const PATTERN_MUSTBEDIR = 8;
    const PATTERN_NEGATIVE = 16;
    const PATTERN_CASE_INSENSITIVE = 32;

    /**
     * @var string the path (or alias) of a PHP file containing MIME type information.
     */
    public static $mimeMagicFile = __DIR__ . '/mimeTypes.php';
    /**
     * @var string the path (or alias) of a PHP file containing MIME aliases.
     */
    public static $mimeAliasesFile = __DIR__ . '/mimeAliases.php';
    private static $_mimeTypes = [];
    private static $_mimeAliases = [];

    /**
     * @param string $file
     * @param string|null $magicFile
     * @param bool $checkExtension
     * @return string|null
     */
    public static function getMimeType(string $file, string $magicFile = null, bool $checkExtension = true): ?string
    {
        if (!extension_loaded('fileinfo')) {
            if ($checkExtension) {
                return static::getMimeTypeByExtension($file, $magicFile);
            }

            throw new InvalidConfigException('The fileinfo PHP extension is not installed.');
        }
        $info = finfo_open(FILEINFO_MIME_TYPE, $magicFile);

        if ($info) {
            $result = finfo_file($info, $file);
            finfo_close($info);

            if ($result !== false) {
                return $result;
            }
        }

        return $checkExtension ? static::getMimeTypeByExtension($file, $magicFile) : null;
    }

    /**
     * @param string $file
     * @param string|null $magicFile
     * @return string|null
     */
    public static function getMimeTypeByExtension(string $file, string $magicFile = null): ?string
    {
        $mimeTypes = static::loadMimeTypes($magicFile);

        if (($ext = pathinfo($file, PATHINFO_EXTENSION)) !== '') {
            $ext = strtolower($ext);
            if (isset($mimeTypes[$ext])) {
                return $mimeTypes[$ext];
            }
        }

        return null;
    }

    /**
     * @param string|null $magicFile
     * @return array
     */
    protected static function loadMimeTypes(string $magicFile = null): array
    {
        if ($magicFile === null) {
            $magicFile = static::$mimeMagicFile;
        }
        if (!isset(self::$_mimeTypes[$magicFile])) {
            self::$_mimeTypes[$magicFile] = require $magicFile;
        }

        return self::$_mimeTypes[$magicFile];
    }

    /**
     * @param string $mimeType
     * @param string|null $magicFile
     * @return array
     */
    public static function getExtensionsByMimeType(string $mimeType, string $magicFile = null): array
    {
        $aliases = static::loadMimeAliases(static::$mimeAliasesFile);
        if (isset($aliases[$mimeType])) {
            $mimeType = $aliases[$mimeType];
        }

        $mimeTypes = static::loadMimeTypes($magicFile);
        return array_keys($mimeTypes, mb_strtolower($mimeType, 'UTF-8'), true);
    }

    /**
     * @param string $aliasesFile
     * @return array
     */
    protected static function loadMimeAliases(string $aliasesFile): array
    {
        if ($aliasesFile === null) {
            $aliasesFile = static::$mimeAliasesFile;
        }
        if (!isset(self::$_mimeAliases[$aliasesFile])) {
            self::$_mimeAliases[$aliasesFile] = require $aliasesFile;
        }

        return self::$_mimeAliases[$aliasesFile];
    }

    /**
     * @param string $src
     * @param string $dst
     * @param array $options
     */
    public static function copyDirectory(string $src, string $dst, array $options = []): void
    {
        $src = static::normalizePath($src);
        $dst = static::normalizePath($dst);

        if ($src === $dst || strpos($dst, $src . DIRECTORY_SEPARATOR) === 0) {
            throw new InvalidArgumentException('Trying to copy a directory to itself or a subdirectory.');
        }
        $dstExists = is_dir($dst);
        if (!$dstExists && (!isset($options['copyEmptyDirectories']) || $options['copyEmptyDirectories'])) {
            static::createDirectory($dst, isset($options['dirMode']) ? $options['dirMode'] : 0775, true);
            $dstExists = true;
        }

        $handle = opendir($src);
        if ($handle === false) {
            throw new InvalidArgumentException("Unable to open directory: $src");
        }
        if (!isset($options['basePath'])) {
            // this should be done only once
            $options['basePath'] = realpath($src);
            $options = static::normalizeOptions($options);
        }
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $from = $src . DIRECTORY_SEPARATOR . $file;
            $to = $dst . DIRECTORY_SEPARATOR . $file;
            if (static::filterPath($from, $options)) {
                if (isset($options['beforeCopy']) && !call_user_func($options['beforeCopy'], $from, $to)) {
                    continue;
                }
                if (is_file($from)) {
                    if (!$dstExists) {
                        // delay creation of destination directory until the first file is copied to avoid creating empty directories
                        static::createDirectory($dst, isset($options['dirMode']) ? $options['dirMode'] : 0775, true);
                        $dstExists = true;
                    }
                    copy($from, $to);
                    if (isset($options['fileMode'])) {
                        @chmod($to, $options['fileMode']);
                    }
                } else {
                    // recursive copy, defaults to true
                    if (!isset($options['recursive']) || $options['recursive']) {
                        static::copyDirectory($from, $to, $options);
                    }
                }
                if (isset($options['afterCopy'])) {
                    call_user_func($options['afterCopy'], $from, $to);
                }
            }
        }
        closedir($handle);
    }

    /**
     * @param string $path
     * @param string $ds
     * @return string
     */
    public static function normalizePath(string $path, string $ds = DIRECTORY_SEPARATOR): string
    {
        $path = rtrim(strtr($path, '/\\', $ds . $ds), $ds);
        if (strpos($ds . $path, "{$ds}.") === false && strpos($path, "{$ds}{$ds}") === false) {
            return $path;
        }
        // the path may contain ".", ".." or double slashes, need to clean them up
        if (strpos($path, "{$ds}{$ds}") === 0 && $ds == '\\') {
            $parts = [$ds];
        } else {
            $parts = [];
        }
        foreach (explode($ds, $path) as $part) {
            if ($part === '..' && !empty($parts) && end($parts) !== '..') {
                array_pop($parts);
            } elseif ($part === '.' || $part === '' && !empty($parts)) {
                continue;
            } else {
                $parts[] = $part;
            }
        }
        $path = implode($ds, $parts);
        return $path === '' ? '.' : $path;
    }

    /**
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @return bool
     */
    public static function createDirectory(string $path, int $mode = 0775, bool $recursive = true): bool
    {
        if (is_dir($path)) {
            return true;
        }
        $parentDir = dirname($path);
        // recurse if parent dir does not exist and we are not at the root of the file system.
        if ($recursive && !is_dir($parentDir) && $parentDir !== $path) {
            static::createDirectory($parentDir, $mode, true);
        }
        try {
            if (!mkdir($path, $mode)) {
                return false;
            }
        } catch (\Exception $e) {
            if (!is_dir($path)) {// https://github.com/yiisoft/yii2/issues/9288
                throw new Exception("Failed to create directory \"$path\": " . $e->getMessage(), $e->getCode(), $e);
            }
        }
        try {
            return chmod($path, $mode);
        } catch (\Exception $e) {
            throw new Exception(
                "Failed to change permissions for directory \"$path\": " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @param array $options
     * @return array
     */
    protected static function normalizeOptions(array $options): array
    {
        if (!array_key_exists('caseSensitive', $options)) {
            $options['caseSensitive'] = true;
        }
        if (isset($options['except'])) {
            foreach ($options['except'] as $key => $value) {
                if (is_string($value)) {
                    $options['except'][$key] = self::parseExcludePattern($value, $options['caseSensitive']);
                }
            }
        }
        if (isset($options['only'])) {
            foreach ($options['only'] as $key => $value) {
                if (is_string($value)) {
                    $options['only'][$key] = self::parseExcludePattern($value, $options['caseSensitive']);
                }
            }
        }

        return $options;
    }

    /**
     * @param string $pattern
     * @param bool $caseSensitive
     * @return array
     */
    private static function parseExcludePattern(string $pattern, bool $caseSensitive): array
    {
        $result = [
            'pattern' => $pattern,
            'flags' => 0,
            'firstWildcard' => false,
        ];

        if (!$caseSensitive) {
            $result['flags'] |= self::PATTERN_CASE_INSENSITIVE;
        }

        if (!isset($pattern[0])) {
            return $result;
        }

        if ($pattern[0] === '!') {
            $result['flags'] |= self::PATTERN_NEGATIVE;
            $pattern = StringHelper::byteSubstr($pattern, 1, StringHelper::byteLength($pattern));
        }
        if (StringHelper::byteLength($pattern) && StringHelper::byteSubstr($pattern, -1, 1) === '/') {
            $pattern = StringHelper::byteSubstr($pattern, 0, -1);
            $result['flags'] |= self::PATTERN_MUSTBEDIR;
        }
        if (strpos($pattern, '/') === false) {
            $result['flags'] |= self::PATTERN_NODIR;
        }
        $result['firstWildcard'] = self::firstWildcardInPattern($pattern);
        if ($pattern[0] === '*' && self::firstWildcardInPattern(StringHelper::byteSubstr(
            $pattern,
            1,
            StringHelper::byteLength($pattern)
        )) === false) {
            $result['flags'] |= self::PATTERN_ENDSWITH;
        }
        $result['pattern'] = $pattern;

        return $result;
    }

    /**
     * @param string $pattern
     * @return int
     */
    private static function firstWildcardInPattern(string $pattern): int
    {
        $wildcards = ['*', '?', '[', '\\'];
        $wildcardSearch = function ($r, $c) use ($pattern) {
            $p = strpos($pattern, $c);

            return $r === false ? $p : ($p === false ? $r : min($r, $p));
        };

        return array_reduce($wildcards, $wildcardSearch, false);
    }

    /**
     * @param string $path
     * @param array $options
     * @return bool
     */
    public static function filterPath(string $path, array $options): bool
    {
        if (isset($options['filter'])) {
            $result = call_user_func($options['filter'], $path);
            if (is_bool($result)) {
                return $result;
            }
        }

        if (empty($options['except']) && empty($options['only'])) {
            return true;
        }

        $path = str_replace('\\', '/', $path);

        if (!empty($options['except'])) {
            if (($except = self::lastExcludeMatchingFromList(
                $options['basePath'],
                $path,
                $options['except']
            )) !== null) {
                return $except['flags'] & self::PATTERN_NEGATIVE;
            }
        }

        if (!empty($options['only']) && !is_dir($path)) {
            if (($except = self::lastExcludeMatchingFromList($options['basePath'], $path, $options['only'])) !== null) {
                // don't check PATTERN_NEGATIVE since those entries are not prefixed with !
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * @param string $basePath
     * @param string $path
     * @param array|null $excludes
     * @return array|null
     */
    private static function lastExcludeMatchingFromList(string $basePath, string $path, ?array $excludes): ?array
    {
        foreach (array_reverse($excludes) as $exclude) {
            if (is_string($exclude)) {
                $exclude = self::parseExcludePattern($exclude, false);
            }
            if (!isset($exclude['pattern']) || !isset($exclude['flags']) || !isset($exclude['firstWildcard'])) {
                throw new InvalidArgumentException('If exclude/include pattern is an array it must contain the pattern, flags and firstWildcard keys.');
            }
            if ($exclude['flags'] & self::PATTERN_MUSTBEDIR && !is_dir($path)) {
                continue;
            }

            if ($exclude['flags'] & self::PATTERN_NODIR) {
                if (self::matchBasename(
                    basename($path),
                    $exclude['pattern'],
                    $exclude['firstWildcard'],
                    $exclude['flags']
                )) {
                    return $exclude;
                }
                continue;
            }

            if (self::matchPathname(
                $path,
                $basePath,
                $exclude['pattern'],
                $exclude['firstWildcard'],
                $exclude['flags']
            )) {
                return $exclude;
            }
        }

        return null;
    }

    /**
     * @param string $baseName
     * @param string $pattern
     * @param int $firstWildcard
     * @param int $flags
     * @return bool
     */
    private static function matchBasename(string $baseName, string $pattern, int $firstWildcard, int $flags): bool
    {
        if ($firstWildcard === false) {
            if ($pattern === $baseName) {
                return true;
            }
        } elseif ($flags & self::PATTERN_ENDSWITH) {
            /* "*literal" matching against "fooliteral" */
            $n = StringHelper::byteLength($pattern);
            if (StringHelper::byteSubstr($pattern, 1, $n) === StringHelper::byteSubstr($baseName, -$n, $n)) {
                return true;
            }
        }

        $matchOptions = [];
        if ($flags & self::PATTERN_CASE_INSENSITIVE) {
            $matchOptions['caseSensitive'] = false;
        }

        return StringHelper::matchWildcard($pattern, $baseName, $matchOptions);
    }

    /**
     * @param string $path
     * @param string $basePath
     * @param string $pattern
     * @param int $firstWildcard
     * @param bool $flags
     * @return bool
     */
    private static function matchPathname(
        string $path,
        string $basePath,
        string $pattern,
        int $firstWildcard,
        bool $flags
    ): bool {
        // match with FNM_PATHNAME; the pattern has base implicitly in front of it.
        if (isset($pattern[0]) && $pattern[0] === '/') {
            $pattern = StringHelper::byteSubstr($pattern, 1, StringHelper::byteLength($pattern));
            if ($firstWildcard !== false && $firstWildcard !== 0) {
                $firstWildcard--;
            }
        }

        $namelen = StringHelper::byteLength($path) - (empty($basePath) ? 0 : StringHelper::byteLength($basePath) + 1);
        $name = StringHelper::byteSubstr($path, -$namelen, $namelen);

        if ($firstWildcard !== 0) {
            if ($firstWildcard === false) {
                $firstWildcard = StringHelper::byteLength($pattern);
            }
            // if the non-wildcard part is longer than the remaining pathname, surely it cannot match.
            if ($firstWildcard > $namelen) {
                return false;
            }

            if (strncmp($pattern, $name, $firstWildcard)) {
                return false;
            }
            $pattern = StringHelper::byteSubstr($pattern, $firstWildcard, StringHelper::byteLength($pattern));
            $name = StringHelper::byteSubstr($name, $firstWildcard, $namelen);

            // If the whole pattern did not have a wildcard, then our prefix match is all we need; we do not need to call fnmatch at all.
            if (empty($pattern) && empty($name)) {
                return true;
            }
        }

        $matchOptions = [
            'filePath' => true
        ];
        if ($flags & self::PATTERN_CASE_INSENSITIVE) {
            $matchOptions['caseSensitive'] = false;
        }

        return StringHelper::matchWildcard($pattern, $name, $matchOptions);
    }

    /**
     * @param string $dir
     * @param array $options
     */
    public static function removeDirectory(string $dir, array $options = []): void
    {
        if (!is_dir($dir)) {
            return;
        }
        if (!empty($options['traverseSymlinks']) || !is_link($dir)) {
            if (!($handle = opendir($dir))) {
                return;
            }
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) {
                    static::removeDirectory($path, $options);
                } else {
                    static::unlink($path);
                }
            }
            closedir($handle);
        }
        if (is_link($dir)) {
            static::unlink($dir);
        } else {
            rmdir($dir);
        }
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function unlink(string $path): bool
    {
        $isWindows = DIRECTORY_SEPARATOR === '\\';

        if (!$isWindows) {
            return unlink($path);
        }

        if (is_link($path) && is_dir($path)) {
            return rmdir($path);
        }

        try {
            return unlink($path);
        } catch (ErrorException $e) {
            // last resort measure for Windows
            if (function_exists('exec') && file_exists($path)) {
                exec('DEL /F/Q ' . escapeshellarg($path));

                return !file_exists($path);
            }

            return false;
        }
    }

    /**
     * @param string $dir
     * @param array $options
     * @return array
     */
    public static function findFiles(string $dir, array $options = []): array
    {
        $dir = self::clearDir($dir);
        $options = self::setBasePath($dir, $options);
        $list = [];
        $handle = self::openDir($dir);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (static::filterPath($path, $options)) {
                if (is_file($path)) {
                    $list[] = $path;
                } elseif (is_dir($path) && (!isset($options['recursive']) || $options['recursive'])) {
                    $list = array_merge($list, static::findFiles($path, $options));
                }
            }
        }
        closedir($handle);

        return $list;
    }

    /**
     * @param string $dir
     * @return string
     */
    private static function clearDir(string $dir)
    {
        if (!is_dir($dir)) {
            throw new InvalidArgumentException("The dir argument must be a directory: $dir");
        }
        return rtrim($dir, DIRECTORY_SEPARATOR);
    }

    /**
     * @param string $dir
     * @param array $options
     * @return array
     */
    private static function setBasePath(string $dir, array $options): array
    {
        if (!isset($options['basePath'])) {
            // this should be done only once
            $options['basePath'] = realpath($dir);
            $options = static::normalizeOptions($options);
        }

        return $options;
    }

    /**
     * @param string $dir
     * @return false|resource
     */
    private static function openDir(string $dir)
    {
        $handle = opendir($dir);
        if ($handle === false) {
            throw new InvalidArgumentException("Unable to open directory: $dir");
        }
        return $handle;
    }

    /**
     * @param string $dir
     * @param array $options
     * @return array
     */
    public static function findDirectories(string $dir, array $options = []): array
    {
        $dir = self::clearDir($dir);
        $options = self::setBasePath($dir, $options);
        $list = [];
        $handle = self::openDir($dir);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path) && static::filterPath($path, $options)) {
                $list[] = $path;
                if (!isset($options['recursive']) || $options['recursive']) {
                    $list = array_merge($list, static::findDirectories($path, $options));
                }
            }
        }
        closedir($handle);

        return $list;
    }
}
