<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

declare(strict_types=1);

namespace Headercat\Statimate\Console;

use Stringable;

final class Worker extends \Workerman\Worker
{
    public static string $command = 'start';
    public static string $logFile = '/dev/null';

    protected static function parseCommand(): void
    {
    }

    public static function log(Stringable|string $msg, bool $decorated = false): void
    {
    }

    public static function safeEcho(string $msg, bool $decorated = false): void
    {
    }

    /**
     * Determine if the provided port is available to open.
     *
     * @param int $port
     *
     * @return bool
     */
    public static function isPortAvailable(int $port): bool
    {
        $connection = @fsockopen('127.0.0.1', $port);
        if (is_resource($connection)) {
            fclose($connection);
            return false;
        }
        return true;
    }
}
