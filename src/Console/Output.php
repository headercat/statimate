<?php

declare(strict_types=1);

namespace Headercat\Statimate\Console;

use Headercat\Statimate\Router\Route;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

final class Output
{
    /**
     * Get console writer instance of container.
     *
     * @return OutputInterface
     *
     * @throws BindingResolutionException
     */
    public static function getWriter(): OutputInterface
    {
        $container = Container::getInstance();
        if (!$container->bound('writer')) {
            $container->instance('writer', new ConsoleOutput());
        }
        $output = $container->make('writer');
        assert($output instanceof ConsoleOutput);
        return $output;
    }

    /**
     * Write message to console.
     *
     * @param string $message Message to output.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public static function write(string $message): void
    {
        self::getWriter()->writeln($message);
    }

    /**
     * @param string $version
     *
     * @return void
     * @throws BindingResolutionException
     */
    public static function title(string $version): void
    {
        self::write('');
        self::write('<bg=blue;options=bold>☀ statimate v' . $version . '</>');
    }

    /**
     * Write step title message to console.
     *
     * @param int    $step  Step number.
     * @param string $title Title of the step.
     *
     * @return void
     * @throws BindingResolutionException
     */
    public static function step(int $step, string $title): void
    {
        $length = ((int)`echo \$COLUMNS`) ?: 80; // @phpstan-ignore-line
        $step = str_pad((string)$step, 2, '0', STR_PAD_LEFT);

        self::write('');
        self::write(str_repeat('-', $length));
        self::write('<options=bold>✨  ' . $step . '. ' . $title . '</>');
        self::write(str_repeat('-', $length));
    }

    /**
     * Write success message to console.
     *
     * @param string     $message Message to output.
     * @param Timer|null $timer   Timer to calculate the execution time.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public static function success(string $message, Timer|null $timer = null): void
    {
        if ($timer) {
            $message = $message . ' (' . round($timer->get(), 2) . 's)';
        }
        self::write('✅  <info>' . $message . '</>');
    }

    /**
     * Write error message to console.
     *
     * @param string     $message Message to output.
     * @param Timer|null $timer   Timer to calculate the execution time.
     *
     * @return never
     *
     * @throws BindingResolutionException
     */
    public static function error(string $message, Timer|null $timer = null): never
    {
        if ($timer) {
            $message = $message . ' (' . round($timer->get(), 2) . 's)';
        }
        self::write('🚨 <fg=red>' . $message . '</>');
        exit(1);
    }

    /**
     * Write compiled item to console.
     *
     * @param Route $route Route of the compiled item.
     * @param Timer $timer Timer to calculate the execution time.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public static function compiled(Route $route, Timer $timer): void
    {
        $length = (((int)`echo \$COLUMNS`) ?: 80) - 22; // @phpstan-ignore-line
        $message = '✅  <fg=' . ($route->isDocument ? 'blue' : 'green') . '>'
            . ($route->isDocument ? 'Docs' : 'Asst') . '</> '
            . str_pad(
                substr($route->route, 0, $length) . (strlen($route->route) > $length ? '...' : ''),
                $length + 3,
            )
            . str_pad(
                '(' . round($timer->get(), 2) . 's)',
                10,
                ' ',
                STR_PAD_LEFT,
            );
        self::write($message);
    }
}
