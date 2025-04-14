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
}
