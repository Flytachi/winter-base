<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base;

final class DebugDump
{
    private function __construct()
    {
    }

    public static function dump(mixed ...$values): never
    {
        if (PHP_SAPI === 'cli') {
            self::dumpCli(...$values);
        } else {
            self::dumpWeb(...$values);
        }
    }

    public static function dumpCli(mixed ...$values): never
    {
        $info = self::info();
        echo "\033[31m" . "[====================== DUMP and DIE ======================]\033[0m\n";
        echo "\033[31m" . ">---------------------------------------------------------- \033[0m\n";
        foreach ($values as $key => $value) {
            echo "\033[31m" . ">- \033[0m\n";
            echo match (gettype($value)) {
                'NULL'               => "\033[37mnull",
                'boolean'            => "\033[32m" . var_export($value, true),
                'integer', 'double'  => "\033[36m" . var_export($value, true),
                'object', 'array'    => "\033[35m" . print_r($value, true),
                'string'             => "\033[33m" . var_export($value, true),
                default              => "\033[31m" . var_export($value, true)
            };
            echo "\033[0m\n";
        }
        echo "\033[31m" . ">---------------------------------------------------------- \033[0m\n";
        echo "\033[31m" . "| Source ===> {$info['file']}({$info['line']}) \033[0m\n";
        echo "\033[31m" . "| Memory ===> {$info['memory']} \033[0m\n";
        echo "\033[31m" . "| Time ===> {$info['delta']} \033[0m\n";
        echo "\033[31m" . "| {$info['timezone']} ===> {$info['time']} \033[0m\n";
        echo "\033[31m" . "[====================== DUMP and DIE ======================]\033[0m\n";
        die();
    }

    public static function dumpWeb(mixed ...$values): never
    {
        $info = self::info();
        echo '<body style="background-color: #0a0f1f">';
        echo '<div style="border: 2px solid #3e006f;border-radius: 7px;padding: 10px;background-color: black;">';
        echo    '<div style="display: flex;justify-content: space-between;margin-top: 8px;margin-bottom: 17px">';
        echo        '<span style="float: left;font-size: 1.2rem; color: #ffffff;">';
        echo            "<span style=\"color: #7f00e0;font-weight: bold;\">DUMP and DIE:</span> "
                            . $info['file'] . " (" . $info['line'] . ")";
        echo        '</span>';
        echo        '<span style="float: right;font-style: italic;">';
        echo            '<span style="color: #adadad">' . $info['time'] . '</span> ';
        echo            '<span style="color: #00ffff">' . $info['timezone'] . '</span>';
        echo        '</span>';
        echo    '</div>';
        echo    '<hr style="border: 1px solid #999999;">';
        echo    '<pre style="margin:10px;white-space: pre-wrap; ';
        echo    'white-space: -moz-pre-wrap;white-space: -o-pre-wrap;word-wrap: break-word;">';
        $countValues = count($values);
        $i = 0;
        foreach ($values as $value) {
            echo match (gettype($value)) {
                'NULL'               => '<span style="color: #999999;">null</span>',
                'boolean'            => '<span style="color: #00ff00;">' . var_export($value, true) . '</span>',
                'integer', 'double'  => '<span style="color: #00ffff;">' . var_export($value, true) . '</span>',
                'object'             => '<span style="color: #ff7033;">' . print_r($value, true) . '</span>',
                'array'              => '<span style="color: #cb71ff;">' . print_r($value, true) . '</span>',
                'string'             => '<span style="color: #e4ff6c;">' . var_export($value, true) . '</span>',
                default              => '<span style="color: #fa5151;">' . var_export($value, true) . '</span>'
            };
            if ($countValues > ++$i) {
                echo '<hr style="border: 1px dashed rgb(68,68,68);">';
            }
        }
        echo    '</pre>';
        echo    '<hr style="border: 1px solid #999999;">';
        echo    '<div style="display: flex;justify-content: space-between;">';
        echo        '<span style="float: left;color: #9e9e9e;font-weight: bold;">Memory ' . $info['memory'] . '</span>';
        echo        '<span style="float: right;color: #9e9e9e;font-style: italic;">Time ' . $info['delta'] . '</span>';
        echo    '</div>';
        echo '</div>';
        echo '</body>';
        die();
    }

    private static function info(): array
    {
        $backtrace = debug_backtrace();
        $line = $backtrace[3]['line'];
        $file = $backtrace[3]['file'];

        defined('WINTER_STARTUP_TIME') or define('WINTER_STARTUP_TIME', microtime(true));
        if (WINTER_STARTUP_TIME !== null) {
            $delta = round(microtime(true) - WINTER_STARTUP_TIME, 3);
            $delta = ($delta < 0.001) ? 0.001 : $delta;
        } else {
            $delta = null;
        }

        return [
            'file' => $file,
            'line' => $line,
            'delta' => $delta,
            'memory' => bytes(memory_get_usage(), 'MiB'),
            'timezone' => date_default_timezone_get(),
            'time' => date(DATE_ATOM),
        ];
    }
}
