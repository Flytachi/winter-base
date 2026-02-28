<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base;

abstract class Header
{
    /**
     * @var array<string, string> $headers
     */
    private static array $headers = [];
    /**
     * @var array<string, string> $initHeaders
     */
    private static array $initHeaders = [];

    private function __construct()
    {
    }

    /**
     * Initializes the custom (outgoing) HTTP headers for the current response.
     *
     * This method allows you to define additional headers that will be sent
     * to the client when {@see Header::setHeaders()} is called.
     *
     * It does not affect or overwrite the incoming request headers that were
     * received from the client. Instead, it stores the provided headers
     * internally in {@see self::$initHeaders} for later use.
     *
     * Example:
     * ```
     * // Recommended path '../public/index.php'
     * Header::initHeaders([
     *     'Content-Type' => 'application/json',
     *     'X-Powered-By' => 'Extra Kernel',
     * ]);
     * // ... other code ...
     *
     * // Using (default use in Router)
     * Header::setHeaders();
     * ```
     *
     * @param array<string,string> $headers
     *     An associative array of header names and their corresponding values.
     *     For example: ['Content-Type' => 'application/json'].
     *
     * @return void
     *
     * @see Header::setHeaders() Sends the headers initialized here.
     * @see Header::getHeaders() Retrieves all current request headers.
     */
    public static function initHeaders(array $headers): void
    {
        self::$initHeaders = $headers;
    }

    /**
     * Sets the headers for the request.
     *
     * This method retrieves the request headers and sets them in the $headers property of the class.
     * If the apache_request_headers() function is available, it is used to retrieve the headers.
     * The headers are then formatted using ucwords() and array_combine() functions to ensure consistent formatting.
     *
     * @return void
     */
    public static function setHeaders(): void
    {
        if (function_exists('apache_request_headers')) {
            $apacheHeaders = apache_request_headers();
            static::$headers = array_combine(
                array_map('ucwords', array_keys($apacheHeaders)),
                array_values($apacheHeaders)
            );
        }
        if (isset($_SERVER['HTTP_TIMEZONE'])) {
            if (date_default_timezone_get() !== $_SERVER['HTTP_TIMEZONE']) {
                if (in_array($_SERVER['HTTP_TIMEZONE'], timezone_identifiers_list(), true)) {
                    date_default_timezone_set($_SERVER['HTTP_TIMEZONE']);
                }
            }
        }
        self::$headers['Ip-Address'] = self::resolveIpAddress();

        if (!empty(self::$initHeaders)) {
            foreach (self::$initHeaders as $key => $value) {
                header("$key: $value");
            }
        }
    }

    /**
     * Retrieves the values in header from the request.
     * @return array<string, string>
     */
    public static function getHeaders(): array
    {
        return static::$headers;
    }

    /**
     * Retrieves the value of a specific header from the request.
     *
     * @param string $key The key of the header to retrieve.
     * @param bool $isUcWords (Optional) Specifies whether the key should
     * be formatted with ucwords before retrieving the value. Default is true.
     *
     * @return string The value of the requested header. If the header is not found, an empty string is returned.
     */
    public static function getHeader(string $key, bool $isUcWords = true): string
    {
        return static::$headers[($isUcWords ? ucwords($key) : $key)] ?? '';
    }

    /**
     * Checks if a given key-value pair exists in the headers.
     *
     * @param string $key The key of the header to check.
     * @param string $value The value of the header to check.
     * @param bool $isUcWords (Optional) Specifies whether the key should
     * be converted to ucwords format before checking. Default is true.
     *
     * @return bool Returns true if the key-value pair exists in the headers, false otherwise.
     */
    public static function inHeader(string $key, string $value, bool $isUcWords = true): bool
    {
        return str_contains((static::$headers[($isUcWords ? ucwords($key) : $key)] ?? ''), $value);
    }

    /**
     * @return null|string
     */
    public static function getIpAddress(): ?string
    {
        return static::$headers['Ip-Address'] ?? null;
    }

    /**
     * Retrieves the User-Agent from the request.
     *
     * @return string|null
     */
    public static function getUserAgent(): ?string
    {
        return static::$headers['User-Agent'] ?? null;
    }

    /**
     * Retrieves the Accept-Language from the request.
     * Example: "en-US,en;q=0.9,ru;q=0.8"
     *
     * @return string|null
     */
    public static function getAcceptLanguage(): ?string
    {
        return static::$headers['Accept-Language'] ?? null;
    }

    /**
     * Retrieves the preferred language from Accept-Language header.
     * Example: "en-US,en;q=0.9" → "en-US"
     *
     * @return string|null
     */
    public static function getPreferredLanguage(): ?string
    {
        $acceptLanguage = static::$headers['Accept-Language'] ?? null;
        if (!$acceptLanguage) return null;
        return trim(explode(',', $acceptLanguage)[0]);
    }

    /**
     * Retrieves the Content-Type from the request.
     * Example: "application/json"
     *
     * @return string|null
     */
    public static function getContentType(): ?string
    {
        return static::$headers['Content-Type'] ?? null;
    }

    /**
     * Checks if the request expects a JSON response.
     *
     * @return bool
     */
    public static function isJson(): bool
    {
        return str_contains(static::$headers['Accept'] ?? '', 'application/json')
            || str_contains(static::$headers['Content-Type'] ?? '', 'application/json');
    }

    /**
     * Checks if the request was made via XMLHttpRequest (AJAX).
     *
     * @return bool
     */
    public static function isAjax(): bool
    {
        return (static::$headers['X-Requested-With'] ?? '') === 'XMLHttpRequest';
    }

    /**
     * Retrieves the Origin from the request.
     * Example: "https://example.com"
     *
     * @return string|null
     */
    public static function getOrigin(): ?string
    {
        return static::$headers['Origin'] ?? null;
    }

    /**
     * Retrieves the Referer from the request.
     * Example: "https://example.com/page"
     *
     * @return string|null
     */
    public static function getReferer(): ?string
    {
        return static::$headers['Referer'] ?? null;
    }

    /**
     * Bearer Token
     *
     * @return string|null
     */
    final public static function getBearerToken(): string|null
    {
        $auth = static::$headers['Authorization'] ?? '';
        return preg_match('/Bearer\s(\S+)/', $auth, $m)
            ? $m[1] : null;
    }

    /**
     * Basic Token
     *
     * @return string|null
     */
    final public static function getBasicToken(): string|null
    {
        $auth = static::$headers['Authorization'] ?? '';
        return preg_match('/Basic\s(\S+)/', $auth, $m)
            ? base64_decode($m[1]) : null;
    }

    /**
     * Resolves the real client IP address from the request.
     *
     * Resolution priority (RFC 7239 compliant):
     *   1. Forwarded (RFC 7239 standard)
     *   2. X-Real-IP (de facto standard, nginx/NPM)
     *   3. X-Forwarded-For (de facto standard, most proxies/LBs)
     *   4. REMOTE_ADDR (direct connection fallback)
     *
     * @return string
     */
    private static function resolveIpAddress(): string
    {
        // RFC 7239: Forwarded: for=203.0.113.5
        if (!empty($_SERVER['HTTP_FORWARDED'])) {
            if (preg_match('/for=["\']?([^;,"\'\s\]]+)/i', $_SERVER['HTTP_FORWARDED'], $m)) {
                $ip = trim($m[1], '"\'[]');  // remove quotes and IPv6 brackets
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        // X-Real-IP — nginx, NPM
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = trim($_SERVER['HTTP_X_REAL_IP']);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }

        // X-Forwarded-For — chain: "203.0.113.5, 10.0.1.4" — first is client
        // FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE — skip internal IPs (spoofing protection)
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        // Direct connection (localhost, tests, no proxy)
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
