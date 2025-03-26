<?php

declare(strict_types=1);

namespace Headercat\Statimate;

use Closure;
use Headercat\Statimate\Router\Route;
use Headercat\Statimate\Router\RouteCollector;
use Headercat\Statimate\Supports\Directory;
use Headercat\Statimate\Template\Renderer;
use LogicException;
use RuntimeException;
use Throwable;

final readonly class Build
{
    /**
     * Constructor.
     *
     * @param Statimate $app
     */
    public function __construct(private Statimate $app)
    {
    }

    /**
     * Build SSG.
     *
     * @param string|null $buildDir Directory to write the output.
     *
     * @return void
     */
    public function build(string|null $buildDir = null): void
    {
        try {
            $this->run(
                function () use ($buildDir) {
                    if ($buildDir === null) {
                        $buildDir = $this->app->buildDir ?? null;
                        if (!$buildDir) {
                            throw new LogicException('Cannot determine the build directory.');
                        }
                    }

                    $buildDir = $this->run(
                        fn() => Directory::clean($buildDir),
                        fn(string $v) => 'Build directory cleaned.',
                    );
                    $routes = $this->run(
                        fn() => $this->getRoutes(),
                        fn(array $routes) => number_format(count($routes)) . ' routes found.',
                    );

                    $this->run(
                        fn() => $this->copyStaticRoutes($buildDir, $routes),
                        fn(array $staticRoutes) => number_format(count($staticRoutes)) . ' static files copied.',
                    );
                    $this->run(
                        fn() => $this->writeDocumentRoutes($buildDir, $routes),
                        fn(array $documentRoutes) => number_format(count($documentRoutes)) . ' document routes written.'
                    );
                    return $buildDir;
                },
                fn(string $buildDir) => 'Build complete. Output to ' . $buildDir,
            );
        } catch (Throwable) {
        }
    }

    /**
     * Serve the request.
     *
     * @throws Throwable
     */
    public function serve(): never
    {
        $tempDir = sys_get_temp_dir() . '/headercat/statimate/serve/' . sha1($this->app->rootDir);
        $this->build($tempDir);

        $acceptHeader = $_SERVER['HTTP_ACCEPT'] ?? 'text/html';
        assert(is_string($acceptHeader));

        $contentType = explode(';', trim(explode(',', $acceptHeader)[0]))[0];
        header('Content-Type: ' . $contentType);

        $requestUri = $_SERVER['REQUEST_URI'];
        assert(is_string($requestUri));

        $path = $tempDir . $requestUri;
        $path = is_dir($path) ? $path . '/index.html' : $path;
        echo file_get_contents($path) ?: '';
        exit();
    }

    /**
     * Run each command.
     *
     * @template T
     *
     * @param Closure():T       $callback Closure which runs the command.
     * @param Closure(T):string $message  Console message builder.
     *
     * @return T
     * @throws Throwable
     */
    private function run(Closure $callback, Closure $message): mixed
    {
        $startTime = microtime();
        try {
            $output = $callback();
        } catch (Throwable $e) {
            if (PHP_SAPI === 'cli') {
                echo '🔴 ' . ($str = $e->getMessage()) . str_repeat(' ', 100 - strlen($str));
                echo $this->getExecutionTime($startTime);
                echo "\n";
                exit(1);
            }
            throw $e;
        }
        if (PHP_SAPI === 'cli') {
            $str = $message($output);
            echo '✅  ' . $str . str_repeat(' ', 100 - strlen($str));
            echo $this->getExecutionTime($startTime);
            echo "\n";
        }
        return $output;
    }

    private function getExecutionTime(string $startTime): string
    {
        $start = explode(' ', $startTime);
        $end = explode(' ', microtime());

        $sec = (int)$end[1] - (int)$start[1];
        $micro = round(((float)$end[0] - (float)$start[0]) * 100);
        return $sec . '.' . str_pad((string)$micro, 2, '0', STR_PAD_LEFT) . 's';
    }

    /**
     * Get routes from RouteCollector.
     *
     * @return list<Route> List of the collected Route instances.
     */
    private function getRoutes(): array
    {
        return new RouteCollector(
            routeDir: $this->app->routeDir,
            documentExtensions: $this->app->documentExtensions,
            excludedExtensions: $this->app->excludedExtensions,
        )->collect();
    }

    /**
     * Copy all non-documentation route files to the build directory.
     *
     * @param string      $buildDir Build directory.
     * @param list<Route> $routes   List of all route instances.
     *
     * @return list<Route> List of non-documentation route instances.
     */
    private function copyStaticRoutes(string $buildDir, array $routes): array
    {
        $staticRoutes = array_values(array_filter($routes, fn(Route $route) => !$route->isDocument));
        foreach ($staticRoutes as $route) {
            $targetPath = $buildDir . $route->routePath;
            if (!is_dir($dirname = dirname($targetPath))) {
                mkdir($dirname, 0777, true);
            }
            if (!copy($route->sourcePath, $targetPath)) {
                throw new RuntimeException(
                    'Cannot copy the static file "' . $route->sourcePath . '".'
                );
            }
        }
        return $staticRoutes;
    }

    /**
     * Write documentation route to the build directory.
     *
     * @param string      $buildDir Build directory.
     * @param list<Route> $routes   List of all route instances.
     *
     * @return list<Route> List of documentation route instances.
     *
     * @throws Throwable
     * @noinspection PhpRedundantVariableDocTypeInspection
     */
    private function writeDocumentRoutes(string $buildDir, array $routes): array
    {
        static $renderer = new Renderer($this->app->rootDir, $this->app->renderers);
        $documentRoutes = array_values(array_filter($routes, fn(Route $route) => $route->isDocument));

        /** @var Route $route */
        foreach ($documentRoutes as $route) {
            /** @var Renderer $renderer */
            $output = $renderer->render($route->sourcePath, $route->placeholders);
            foreach ($route->layouts as $layout) {
                $output = $renderer->render($layout, [
                    ...$route->placeholders,
                    'content' => $output,
                ]);
            }

            $targetPath = $buildDir . $route->routePath;
            if (!$this->writeFile($targetPath, $output)) {
                throw new RuntimeException('Cannot write file "' . $targetPath . '".');
            }
        }
        return $documentRoutes;
    }

    /**
     * Write file to the path.
     *
     * @param string $path    Path to write.
     * @param string $content Content to write.
     *
     * @return bool
     */
    private function writeFile(string $path, string $content): bool
    {
        $dirname = dirname($path);
        if (!is_dir($dirname)) {
            mkdir($dirname, 0777, true);
        }
        return file_put_contents($path, $content) !== false;
    }
}
