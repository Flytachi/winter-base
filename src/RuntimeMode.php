<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base;

enum RuntimeMode
{
    /** Plain CLI — console commands, cron, scripts */
    case Console;
    /** Built-in PHP web server — `php -S` */
    case CliServer;
    /** PHP-FPM / FastCGI */
    case Fpm;
    /** Swoole HTTP/TCP server worker */
    case Swoole;
}
