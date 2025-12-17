<?php

declare(strict_types=1);

namespace Flytachi\Winter\Base\Log;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Registry;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class LoggerRegistry
{
    /** @var LoggerInterface|null */
    private static ?LoggerInterface $logger = null;

    /**
     * @param LoggerInterface $logger
     */
    public static function setInstance(LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    /**
     * @param string|null $name
     * @return LoggerInterface
     */
    public static function instance(?string $name = null): LoggerInterface
    {
        self::init();

        if ($name !== null && self::$logger instanceof Logger) {
            if (Registry::hasLogger($name)) {
                return Registry::getInstance($name);
            }

            $newLogger = self::$logger->withName($name);
            Registry::addLogger($newLogger);
            return $newLogger;
        }

        return self::$logger;
    }

    private function __construct()
    {
    }
    private function __clone()
    {
    }

    public function __serialize(): array
    {
        return [];
    }

    public function __unserialize(array $data): void
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    private static function init(): void
    {
        if (self::$logger !== null) {
            return;
        }

        if (class_exists(Logger::class)) {
            self::$logger = new Logger('BASE');

            try {
                $projectRoot = self::findRootPath();
                if ($projectRoot) {
                    $logDir = $projectRoot . '/storage/logs';
                    if (!is_dir($logDir) && !@mkdir($logDir, 0775, true)) {
                        throw new \RuntimeException(
                            sprintf('Log directory "%s" could not be created.', $logDir)
                        );
                    }
                    $logFile = $logDir . '/winter-base.log';
                    self::$logger->pushHandler(new StreamHandler($logFile));
                }
            } catch (\Exception $e) {
                error_log(self::class
                    . ' Warning: Failed to configure Monolog handler. '
                    . $e->getMessage());
            }
        } else {
            self::$logger = new NullLogger();
        }
    }

    private static function findRootPath(): ?string
    {
        try {
            $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);

            $classLoaderFile = $reflection->getFileName();
            if ($classLoaderFile === false) {
                return null;
            }

            $vendorDir = dirname($classLoaderFile, 2);
            $projectRoot = dirname($vendorDir);

            if (is_dir($projectRoot)) {
                return $projectRoot;
            }
        } catch (\ReflectionException $e) {
            return null;
        }

        return null;
    }
}
