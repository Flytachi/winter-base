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
        if (isset($_SERVER['REMOTE_ADDR'])) {
            self::$headers['Ip-Address'] = $_SERVER['REMOTE_ADDR'];
        }

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
     * @return null|string
     */
    public static function getIpAddress(): ?string
    {
        return static::$headers['Ip-Address'];
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
}
