<?php

declare(strict_types=1);

namespace Headercat\Statimate\Console;

use FilesystemIterator;
use Headercat\Statimate\Compiler\Compiler;
use Headercat\Statimate\Config\StatimateConfig;
use Headercat\Statimate\Router\Route;
use Headercat\Statimate\Router\RouteCollector;
use Headercat\Statimate\Writer\Writer;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Protocols\Http\Response;
use Workerman\Protocols\Http\ServerSentEvents;

final class Application extends \Silly\Application
{
    private const string VERSION = '0.0.3';

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct('☀ statimate.', self::VERSION);
        $this->useContainer(Container::getInstance());

        $this->command('init', $this->initCommand(...))
            ->descriptions('Initialize a statimate project.');
        $this->command('build [config]', $this->buildCommand(...))
            ->descriptions('Build the statimate static site', [
                'config' => 'PHP file which return a StatimateConfig instance.',
            ]);
        $this->command('serve [port] [config]', $this->serveCommand(...))
            ->descriptions('Serve the statimate static site for development purpose', [
                'port' => 'Port number to serve.',
                'config' => 'PHP file which return a StatimateConfig instance.',
            ]);
    }

    /**
     * Init command.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function initCommand(): void
    {
        $globalTimer = new Timer();
        Output::title($this->getVersion());

        $currentDir = getcwd();
        if (!$currentDir) {
            Output::error('Cannot determine the current directory.');
            exit(1);
        }

        $files = array_diff(scandir($currentDir), [ '.', '..' ]);
        if (!empty($files)) {
            Output::error('The current directory is not empty.');
            exit(1);
        }

        $timer = new Timer();
        Output::step(1, 'Create project files');

        if (!file_put_contents($currentDir . '/composer.json', <<<JSON
{
  "description": "Statimate project",
  "type": "project",
  "require-dev": {
    "headercat/statimate": "{$this->getVersion()}"
  }
}
JSON)) {
            Output::error('Cannot create composer.json file.', $globalTimer);
            exit(1);
        }
        if (!file_put_contents($currentDir . '/statimate.config.php', <<<'PHP'
<?php

declare(strict_types=1);

use Headercat\Statimate\Config\StatimateConfig;

return new StatimateConfig();
PHP)) {
            Output::error('Cannot create statimate.config.php file.', $globalTimer);
            exit(1);
        }
        if (!mkdir($currentDir . '/routes')) {
            Output::error('Cannot create routes directory.', $globalTimer);
            exit(1);
        }
        if (!file_put_contents($currentDir . '/routes/#layout.blade.php', <<<'HTML'
<!DOCTYPE HTML>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Statimate</title>
</head>
<body>
<main>
    {!! $content !!}
</main>
</body>
</html>
HTML)) {
            Output::error('Cannot create #layout.blade.php file.', $globalTimer);
            exit(1);
        }
        if (!file_put_contents($currentDir . '/routes/index.blade.php', <<<'HTML'
<h1>Hello, world!</h1>
HTML)) {
            Output::error('Cannot create index.blade.php file.', $globalTimer);
            exit(1);
        }
        Output::success('Project files created.', $timer);

        $timer = new Timer();
        Output::step(2, 'Install dependencies');

        exec('composer install', $_, $returnCode);
        Output::write('');

        if ($returnCode !== 0) {
            Output::error('Failed to install dependencies.', $globalTimer);
            exit(1);
        }
        Output::success('Dependencies installed.', $timer);

        Output::step(3, 'Complete initialization');
        Output::success('Project initialized. Run `statimate serve` to start your development.', $globalTimer);
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
            exit(1);
        }
        Output::success(number_format(count($routes)) . ' routes found.', $routeCollectorTimer);

        $clearTimer = new Timer();
        Output::step(2, 'Clean build directory');

        try {
            $writer = new Writer($config);
            $writer->clear();
        } catch (Throwable $e) {
            Output::error($e->getMessage(), $globalTimer);
            exit(1);
        }
        Output::success('Build directory cleaned.', $clearTimer);

        $compileTimer = new Timer();
        Output::step(3, 'Compile routes');

        try {
            $compiler = new Compiler($config);
            $compiledCount = [0, 0];
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
            exit(1);
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
     * Serve command.
     *
     * @param string      $port   Port number to serve.
     * @param string|null $config Configuration file path.
     *
     * @return void
     *
     * @throws BindingResolutionException
     */
    public function serveCommand(string $port = '8080', string|null $config = null): void
    {
        $port = (int)$port;
        if (!$port || $port < 1 || $port > 65535) {
            Output::error('Port number must be an integer between 1 and 65535.');
            exit(1);
        }
        if (!Worker::isPortAvailable($port)) {
            Output::error('Port ' . $port . ' is not available.');
            exit(1);
        }

        $config = $this->getConfig($config);
        Output::title($this->getVersion());

        $config->setBuildDir(sys_get_temp_dir() . '/headercat/statimate/serve');
        $routeCollector = new RouteCollector($config);
        $writer = new Writer($config);
        $compiler = new Compiler($config);
        $writer->clear();

        $sseScript = '<script>(() => {
            new EventSource(`${location.protocol}//${location.host}/__statimate_broadcast`)
                .addEventListener("message", () => location.reload())
        }) ();</script>';

        $lastMtime = time();
        $routes = [];
        $scoped = [$config, $routeCollector, $writer, $compiler, $sseScript, &$lastMtime, &$routes];

        /** @noinspection PhpObjectFieldsAreOnlyWrittenInspection */
        $worker = new Worker('http://0.0.0.0:' . $port);
        $worker->onMessage = function (TcpConnection $connection, Request $request) use ($scoped): bool|null {
            [$config, $routeCollector, $writer, $compiler, $sseScript, &$lastMtime, &$routes] = $scoped;

            if ($request->path() === '/__statimate_broadcast') {
                if ($request->header('accept') !== 'text/event-stream') {
                    return $connection->send(new Response(302, [
                        'Location' => 'https://github.com/headercat/statimate',
                    ]));
                }
                $connection->send(new Response(200, [
                    'Content-Type' => 'text/event-stream',
                ])->withBody("\r\n"));

                $interval = \Workerman\Timer::add(0.5, function () use ($config, $connection, &$lastMtime, &$interval) {
                    if ($connection->getStatus() !== TcpConnection::STATUS_ESTABLISHED) {
                        \Workerman\Timer::del($interval ?? 0);
                        return;
                    }
                    $iterator = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($config->projectDir, FilesystemIterator::SKIP_DOTS)
                    );
                    foreach ($iterator as $file) {
                        assert($file instanceof SplFileInfo);
                        clearstatcache(true, $path = $file->getRealPath());
                        if ($lastMtime < ($time = filemtime($path))) {
                            $lastMtime = $time;
                            $connection->send(new ServerSentEvents([
                                'event' => 'message',
                                'data' => 'reload',
                            ]));
                            return;
                        }
                    }
                });
                return null;
            }

            try {
                $path = '/' . trim($request->path(), '/');
                $realPath = realpath($config->buildDir . $path);

                $findRoute = function () use (&$routes, &$path): Route|null {
                    if (!($route = array_find($routes, fn(Route $r) => $r->route === $path))) {
                        $path = '/' . trim($path . '/index.html', '/');
                        return array_find($routes, fn(Route $route) => $route->route === $path);
                    }
                    return $route;
                };

                if (!$realPath) {
                    $routes = $routeCollector->collect($config->routeDir);
                    if (!($route = $findRoute())) {
                        return $connection->send(new Response(404));
                    }
                    if (!$route->isDocument) {
                        $timer = new Timer();
                        $writer->copy($route->route, $route->sourcePath);
                        Output::compiled($route, $timer);
                        return $connection->send(new Response()->withFile($config->buildDir . $route->route));
                    }
                }
                if (!isset($route) && !($route = $findRoute())) {
                    return $connection->send(new Response(404));
                }

                if (!$route->isDocument) {
                    clearstatcache(true, $route->sourcePath);
                    clearstatcache(true, $config->buildDir . $route->route);
                    if (filemtime($config->buildDir . $route->route) < filemtime($route->sourcePath)) {
                        $timer = new Timer();
                        $writer->copy($route->route, $route->sourcePath);
                        Output::compiled($route, $timer);
                    }
                    return $connection->send(new Response()->withFile($config->buildDir . $route->route));
                }

                $timer = new Timer();
                $writer->write($route->route, $compiler->compile($route));
                Output::compiled($route, $timer);

                $content = file_get_contents($config->buildDir . $route->route);
                if ($content === false) {
                    return $connection->send(new Response(404));
                }

                $content = $content . $sseScript;
                return $connection->send(
                    new Response(200)
                        ->withHeader('Content-Type', 'text/html')
                        ->withBody($content)
                );
            } catch (Throwable $e) {
                Output::error($e->getMessage());
                return $connection->send(new Response(500));
            }
        };

        Output::success('Listen on: http://localhost:' . $port);
        Output::write('');

        Worker::runAll();
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
            exit(1);
        }
        try {
            $output = include $configPath;
            if (!$output instanceof StatimateConfig) {
                Output::error('The configuration file "' . $configPath . '" does not return a StatimateConfig instance.');
                exit(1);
            }
            return $output;
        } catch (Throwable) {
            Output::error('Configuration file "' . $configPath . '" has some unhandled error.');
            exit(1);
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
                exit(1);
            } catch (Throwable) {
            }
            return 1;
        }
    }
}
