<?php

declare(strict_types=1);

use Headercat\Statimate\Compiler\Compiler;
use Headercat\Statimate\Config\StatimateConfig;
use Headercat\Statimate\Router\RouteCollector;
use Headercat\Statimate\Writer\Writer;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use Silly\Application;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

error_reporting(~E_ALL);

/**
 * Load composer autoloader.
 */
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else if (file_exists(__DIR__ . '/../../../autoload.php')) {
    /** @noinspection PhpIncludeInspection */
    require_once __DIR__ . '/../../../autoload.php';
} else {
    require_once getenv('HOME') . '/.composer/vendor/autoload.php';
}

/**
 * Load application version.
 */
if (file_exists($composerJsonPath = __DIR__ . '/../composer.json')) {
    $composerData = json_decode(file_get_contents($composerJsonPath) ?: '{}');
    if (is_object($composerData) && property_exists($composerData, 'version')) {
        $version = $composerData->version;
    }
}

/**
 * Get configuration instance from provided config path.
 *
 * @param string|null $configPath
 *
 * @return StatimateConfig
 * @throws BindingResolutionException
 */
function config(string|null $configPath): StatimateConfig
{
    if (!$configPath || !file_exists($configPath)) {
        error('Cannot find the statimate configuration file.');
    }
    try {
        $output = include $configPath;
        if (!$output instanceof StatimateConfig) {
            error('Configuration file "' . $configPath . '" does not return a StatimateConfig instance.');
        }
        return $output;
    } catch (Throwable $e) {
        error('Configuration file "' . $configPath . '" has some unhandled error: ' . $e->getMessage());
    }
}

/**
 * Get writer instance.
 *
 * @return OutputInterface
 * @throws BindingResolutionException
 */
function writer(): OutputInterface
{
    $container = Container::getInstance();
    if (!$container->bound('writer')) {
        $container->instance('writer', new ConsoleOutput());
    }
    $output = $container->make('writer');
    assert($output instanceof OutputInterface);
    return $output;
}

/**
 * Print the message.
 *
 * @param string $message
 *
 * @return void
 * @throws BindingResolutionException
 */
function output(string $message): void
{
    writer()->writeln($message);
}

/**
 * Print the error message and exit.
 *
 * @param string $message
 *
 * @return never
 * @throws BindingResolutionException
 */
function error(string $message): never
{
    output('<fg=red>' . $message . '</>');
    exit(1);
}

/**
 * Print the table.
 *
 * @param list<string>                      $headers
 * @param list<list<string|TableSeparator>> $rows
 *
 * @throws BindingResolutionException
 */
function table(array $headers = [], array $rows = []): void
{
    $table = new Table(writer());
    $table->setHeaders($headers)->setRows($rows)->render();
}

function mtimeDiff(string $from, string $to): float
{
    [ $sMicro, $sSec ] = explode(' ', $from);
    [ $eMicro, $eSec ] = explode(' ', $to);
    $micro = (round(((float) $eMicro) * 100) - round(((float) $sMicro) * 100)) / 100;
    return ((int) $eSec - (int) $sSec) + $micro;
}

/**
 * Get default statimate configuration path.
 */
$configPath = getcwd() . '/statimate.config.php';
if (!file_exists($configPath)) {
    $configPath = null;
}

/**
 * Create application.
 */
$app = new Application('Statimate', $version ?? '0.0.1');
$app->useContainer(Container::getInstance());

/**
 * Register a build command.
 */
$app->command('build [config]', function (string|null $config) use ($configPath) {
    $gMtime = microtime();
    $config = config($config ?? $configPath);

    $routeCollector = new RouteCollector($config);
    $routes = $routeCollector->collect($config->routeDir);

    $compiler = new Compiler($config);
    $writer = new Writer($config);
    $writer->clear();

    $result = [];
    foreach ($routes as $i => $route) {
        $mtime = microtime();
        if ($route->isDocument) {
            $writer->write($route->route, $compiler->compile($route));
        } else {
            $writer->copy($route->route, $route->sourcePath);
        }
        $mDiff = mtimeDiff($mtime, microtime());
        $result[] = [
            '#' . ($i + 1),
            $route->route,
            $route->isDocument ? '<bg=blue>Document</>' : '<bg=green>Asset</>',
            ($mDiff > 10 ? '<bg=red>' : ($mDiff > 5 ? '<bg=yellow>' : '<bg=green>')) . $mDiff . 's</>',
        ];
    }
    table(['#', 'Route', 'Type', 'Runtime'], $result);
    output('<info>Build complete in ' . mtimeDiff($gMtime, microtime()) . 's</>');
    output('<info>Output: </>' . $config->buildDir);
    exit();
})->descriptions('Build the statimate static site', [
    'config' => 'PHP file which return a StatimateConfig instance.',
]);

return $app;
