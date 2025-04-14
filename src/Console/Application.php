<?php

declare(strict_types=1);

namespace Headercat\Statimate\Console;

use Headercat\Statimate\Compiler\Compiler;
use Headercat\Statimate\Config\StatimateConfig;
use Headercat\Statimate\Router\RouteCollector;
use Headercat\Statimate\Writer\Writer;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

final class Application extends \Silly\Application
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct('☀ statimate.', $this->getVersionFromComposer());
        $this->useContainer(Container::getInstance());

        $this->command('build [config]', $this->buildCommand(...))
            ->descriptions('Build the statimate static site', [
                'config' => 'PHP file which return a StatimateConfig instance.',
            ]);
    }

    /**
     * Build command.
     *
     * @param string|null $config Configuration file path.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function buildCommand(string|null $config): void
    {
        $globalTimer = new Timer();
        $config = $this->getConfig($config);
        Output::title($this->getVersion());

        $routeCollectorTimer = new Timer();
        Output::step(1, 'Collect routes');

        try {
            $routeCollector = new RouteCollector($config);
            $routes = $routeCollector->collect($config->routeDir);
            if (empty($routes)) {
                throw new RuntimeException('No routes have been found.');
            }
        } catch (Throwable $e) {
            Output::error($e->getMessage(), $globalTimer);
        }
        Output::success(number_format(count($routes)) . ' routes found.', $routeCollectorTimer);

        $clearTimer = new Timer();
        Output::step(2, 'Clean build directory');

        try {
            $writer = new Writer($config);
            $writer->clear();
        } catch (Throwable $e) {
            Output::error($e->getMessage(), $globalTimer);
        }
        Output::success('Build directory cleaned.', $clearTimer);

        $compileTimer = new Timer();
        Output::step(3, 'Compile routes');

        try {
            $compiler = new Compiler($config);
            $compiledCount = [ 0, 0 ];
            foreach ($routes as $route) {
                $timer = new Timer();
                if ($route->isDocument) {
                    $compiledCount[0]++;
                    $writer->write($route->route, $compiler->compile($route));
                } else {
                    $compiledCount[1]++;
                    $writer->copy($route->route, $route->sourcePath);
                }
                Output::compiled($route, $timer);
            }
        } catch (Throwable $e) {
            Output::error($e->getMessage(), $globalTimer);
        }
        Output::success(
            number_format($compiledCount[0]) . ' document compiled, '
            . number_format($compiledCount[1]) . ' asset copied.',
            $compileTimer,
        );

        Output::step(4, 'Build complete');
        Output::success('Build directory: ' . $config->buildDir, $globalTimer);
        Output::write('');
    }

    /**
     * Get version string from composer.json.
     *
     * @return string
     */
    private function getVersionFromComposer(): string
    {
        if (file_exists($composerJsonPath = __DIR__ . '/../../composer.json')) {
            $composerData = json_decode(file_get_contents($composerJsonPath) ?: '{}');
            assert(is_object($composerData));
            if (property_exists($composerData, 'version')) {
                $version = $composerData->version;
                assert(is_string($version));
                return $version;
            }
        }
        return '0.0.1';
    }

    /**
     * Get statimate configuration instance from provided config path.
     *
     * @param string|null $configPath
     *
     * @return StatimateConfig
     *
     * @throws BindingResolutionException
     */
    private function getConfig(string|null $configPath): StatimateConfig
    {
        if (!$configPath) {
            $configPath = realpath(getcwd() . '/statimate.config.php');
        } else {
            $configPath = realpath(getcwd() . '/' . $configPath);
        }
        if (!$configPath || !file_exists($configPath)) {
            Output::error('Cannot find a proper statimate configuration file.');
        }
        try {
            $output = include $configPath;
            if (!$output instanceof StatimateConfig) {
                Output::error('The configuration file "' . $configPath . '" does not return a StatimateConfig instance.');
            }
            return $output;
        } catch (Throwable) {
            Output::error('Configuration file "' . $configPath . '" has some unhandled error.');
        }
    }

    /**
     * Run the application.
     *
     * @param InputInterface|null  $input
     * @param OutputInterface|null $output
     *
     * @return int
     */
    public function run(?InputInterface $input = null, ?OutputInterface $output = null): int
    {
        try {
            return parent::run($input, $output);
        } catch (Throwable $e) {
            try {
                Output::error($e->getMessage());
            } catch (Throwable) {
            }
            return 1;
        }
    }
}
