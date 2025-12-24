<?php

declare(strict_types=1);

if (!function_exists('env')) {
    function env(?string $name = null, bool|int|float|string|null $default = null): bool|int|float|string|null
    {
        if (!isset($_ENV[$name])) {
            return $default ?? null;
        }
        $value = $_ENV[$name];
        if (is_string($value)) {
            if (strtolower($value) === 'true') {
                return true;
            } elseif (strtolower($value) === 'false') {
                return false;
            }

            if (is_numeric($value)) {
                if (str_contains($value, '.')) {
                    return (float)$value;
                }
                // Иначе преобразуем в int
                return (int)$value;
            }
        }
        return $value;
    }
}

if (!function_exists('bytes')) {
    function bytes($bytes, $force_unit = null, $format = null, $si = true): string
    {
        // Format string
        $format = ($format === null) ? '%01.2f %s' : (string) $format;

        if (!$si or str_contains($force_unit, 'i')) {
            // IEC prefixes (binary)
            $units = array('B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB');
            $mod   = 1024;
        } else {
            // SI prefixes (decimal)
            $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB');
            $mod   = 1000;
        }

        // Determine the unit to use
        if (($power = array_search((string) $force_unit, $units)) === false) {
            $power = ($bytes > 0) ? floor(log($bytes, $mod)) : 0;
        }

        return sprintf($format, $bytes / pow($mod, $power), $units[$power]);
    }

}

if (!function_exists('dd')) {
    function dd(mixed ...$values): never
    {
        Flytachi\Winter\Base\DebugDump::dump(...$values);
    }
}

if (!function_exists('scanFindAllFile')) {
    function scanFindAllFile(string $rootDir, ?string $extension = null, array $ignoreFolder = []): array
    {
        $files = [];

        // Получаем все файлы и директории
        $items = glob($rootDir . '/*');

        foreach ($items as $item) {
            if (is_dir($item)) {
                // Рекурсивный вызов для поддиректорий
                if (
                    empty($ignoreFolder) ||
                    !in_array($item, $ignoreFolder)
                ) {
                    $files = array_merge($files, scanFindAllFile($item, $extension, $ignoreFolder));
                }
            } else {
                if ($extension != null && pathinfo($item, PATHINFO_EXTENSION) === $extension) {
                    $files[] = $item;
                } elseif ($extension == null) {
                    $files[] = $item;
                }
            }
        }

        return $files;
    }
}


if (!function_exists('flushDirectory')) {
    /**
     * Flushes a directory recursively, deleting all its files and subdirectories.
     *
     * @param string $dirPath The path to the directory to be flushed.
     * @param string $rootDirPath The path to the root directory.
     * @param array $excludedDirPaths An optional array of directory paths to be excluded from deletion.
     * @param array $excludedFileNames An optional array of file names to be excluded from deletion.
     * @param callable|null $callback An optional callback function to be called for each deleted file or
     * directory. The function must accept an associative array as an argument, containing
     * the "path", "status" and "is_dir" keys.
     *
     * @return void
     */
    function flushDirectory(
        string $dirPath,
        string $rootDirPath,
        array $excludedDirPaths = [],
        array $excludedFileNames = [],
        ?callable $callback = null
    ): void {
        $relPath = trim(str_replace($rootDirPath, '', $dirPath), '/\\');
        $excludedDirPaths = array_map(fn($path) => trim($path, '/\\'), $excludedDirPaths);
        $excludedFileNames = array_map(fn($path) => trim($path, '/\\'), $excludedFileNames);
        if (in_array($relPath, $excludedDirPaths)) {
            return;
        }
        $files = array_diff(scandir($dirPath), array('.','..'));

        foreach ($files as $file) {
            if (is_file("$dirPath/$file")) {
                if (!in_array(basename($file), $excludedFileNames)) {
                    $unlinkStatus = unlink("$dirPath/$file");
                    if ($callback !== null) {
                        call_user_func($callback, [
                            'path' => $relPath . '/' . $file,
                            'status' => $unlinkStatus,
                            'is_dir' => false
                        ]);
                    }
                }
            } elseif (is_dir("$dirPath/$file")) {
                flushDirectory("$dirPath/$file", $rootDirPath, $excludedDirPaths, $excludedFileNames, $callback);
            }
        }
        if ($dirPath != $rootDirPath) {
            $rmdirStatus = rmdir($dirPath);
            if ($callback !== null) {
                call_user_func($callback, ['path' => $relPath, 'status' => $rmdirStatus, 'is_dir' => true]);
            }
        }
    }
}

if (!function_exists('parseUrlDetail')) {
    function parseUrlDetail(string $url): array
    {
        $parsedUrl = parse_url($url);
        $params = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $params);
        }

        return [
            'scheme' => $parsedUrl['scheme'] ?? null,
            'host' => $parsedUrl['host'] ?? null,
            'port' => $parsedUrl['port'] ?? null,
            'user' => $parsedUrl['user'] ?? null,
            'pass' => $parsedUrl['pass'] ?? null,
            'path' => $parsedUrl['path'] ?? null,
            'query' => $params,
            'fragment' => $parsedUrl['fragment'] ?? null
        ];
    }
}

if (!function_exists('multiCopy')) {
    /**
     * Copies files and directories from the source directory to the destination directory recursively.
     *
     * @param string $source The path to the source directory.
     * @param string $dest The path to the destination directory.
     * @param bool $over An optional flag to indicate whether existing files and directories
     * in the destination directory should be overwritten. Defaults to false.
     *
     * @return void
     */
    function multiCopy(string $source, string $dest, bool $over = false): void
    {
        if (!is_dir($dest)) {
            mkdir($dest);
        }
        if ($handle = opendir($source)) {
            while (false !== ($file = readdir($handle))) {
                if ($file != '.' && $file != '..') {
                    $path = $source . '/' . $file;
                    if (is_file($path)) {
                        if (!is_file((string) ($dest . '/' . $file || $over))) {
                            if (!@copy($path, $dest . '/' . $file)) {
                                echo "('.$path.') Ошибка!!! ";
                            }
                        }
                    } elseif (is_dir($path)) {
                        if (!is_dir($dest . '/' . $file)) {
                            mkdir($dest . '/' . $file);
                        }
                        multiCopy($path, $dest . '/' . $file, $over);
                    }
                }
            }
            closedir($handle);
        }
    }
}

if (!function_exists('dashAsciiToCamelCase')) {
    /**
     * Converts a dash-separated ASCII string to camelCase format.
     *
     * This function processes an ASCII string where words are separated by dashes (`-`)
     * and converts it into camelCase notation. The first word remains in the lowercase,
     * and each later word starts with an uppercase letter. The function assumes
     * that the input contains only ASCII characters and does not perform UTF-8 handling.
     *
     * Example:
     *  ```
     *  dashAsciiToCamelCase('hello-world-example') // result 'helloWorldExample'
     *  ```
     *
     * @param string $str The dash-separated ASCII string to convert.
     *
     * @return string The camelCase formatted string.
     */
    function dashAsciiToCamelCase(string $str): string
    {
        $len = strlen($str);
        $result = [];
        $upperNext = false;

        for ($i = 0; $i < $len; $i++) {
            $char = $str[$i];

            if ($char === '-') {
                $upperNext = true;
                continue;
            }

            $result[] = $upperNext ? strtoupper($char) : $char;
            $upperNext = false;
        }

        return implode('', $result);
    }
}

if (!function_exists('timezoneToOffset')) {
    function timezoneToOffset(string $timezone): ?string
    {
        try {
            $tz = new \DateTimeZone($timezone);
            $dt = new \DateTime('now', $tz);
            return $dt->format('P');
        } catch (DateInvalidTimeZoneException|DateMalformedStringException $e) {
            return null;
        }
    }
}
