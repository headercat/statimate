<?php

declare(strict_types=1);

namespace Headercat\Statimate\Router;

use Closure;
use FilesystemIterator;
use Headercat\Statimate\Config\StatimateConfig;
use LogicException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;

final class RouteCollector
{
    /**
     * @var list<string> Extension names with a leading dot, which should be treated as document routes.
     */
    private array $documentExtensions;

    /**
     * @var array<string, list<string>> Map of a parameter handler file path and it's result parameter strings.
     */
    private array $parameterResults = [];

    /**
     * Constructor.
     *
     * @param StatimateConfig $config Statimate configuration.
     */
    public function __construct(StatimateConfig $config)
    {
        $this->documentExtensions = array_keys($config->documentCompilers);
    }

    /**
     * Collect routes from the provided source directory.
     *
     * @param string $sourceDir Directory to collect routes.
     *
     * @return list<Route>
     */
    public function collect(string $sourceDir): array
    {
        $realSourceDir = realpath($sourceDir);
        if (!$realSourceDir) {
            throw new UnexpectedValueException(sprintf(
                'Argument #1 $sourceDir must be a valid directory, but "%s" given.',
                $sourceDir,
            ));
        }
        $routes = [];
        foreach ($this->scanDirectory($realSourceDir) as $file) {
            $routes = array_merge($routes, $this->createRoutes($realSourceDir, $file));
        }

        $registeredRoutePaths = [];
        foreach ($routes as $route) {
            if (array_key_exists($route->route, $registeredRoutePaths)) {
                throw new LogicException(sprintf(
                    'Route "%s" from "%s" already in used by "%s".',
                    $route->route,
                    $route->sourcePath,
                    $registeredRoutePaths[$route->route],
                ));
            }
            $registeredRoutePaths[$route->route] = $route->sourcePath;
        }
        return $routes;
    }

    /**
     * Scan files recursively from the provided directory.
     *
     * @param string $dir
     *
     * @return list<string>
     */
    private function scanDirectory(string $dir): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );
        $files = [];
        foreach ($iterator as $file) {
            assert($file instanceof SplFileInfo);
            if ($file->isDir() || str_starts_with($file->getFilename(), '#')) {
                continue;
            }
            $files[] = $file->getPathname();
        }
        return $files;
    }

    /**
     * Create a route instance from the provided source directory and specific file path.
     *
     * @param string $sourceDir  Directory that includes route files.
     * @param string $sourcePath File which should have been routed.
     *
     * @return list<Route>
     */
    private function createRoutes(string $sourceDir, string $sourcePath): array
    {
        if (!str_starts_with($sourcePath, $sourceDir)) {
            throw new UnexpectedValueException(sprintf(
                'Argument #2 $sourcePath must start with argument #1 $sourceDir ("%s"), but "%s" given.',
                $sourceDir,
                $sourcePath,
            ));
        }

        $isDocument = array_any(
            $this->documentExtensions,
            fn($ext) => str_ends_with($sourcePath, $ext)
        );
        if ($isDocument) {
            return $this->createDocumentRoutes($sourceDir, $sourcePath);
        }

        $routePath = substr($sourcePath, strlen($sourceDir));
        return [new Route($routePath, $sourcePath, false, [])];
    }

    /**
     * Create a document route instance from the provided source directory and specific file path.
     *
     * @param string $sourceDir  Directory that includes route files.
     * @param string $sourcePath File which should have been routed.
     *
     * @return list<Route>
     */
    private function createDocumentRoutes(string $sourceDir, string $sourcePath): array
    {
        $routePath = substr($sourcePath, strlen($sourceDir));
        $segments = explode('/', $routePath);

        $parameters = [];
        foreach ($segments as $i => $segment) {
            if (!str_starts_with($segment, '#[') || !str_ends_with($segment, ']')) {
                continue;
            }
            $parameterName = substr($segment, 2, -1);
            if (in_array($parameterName, $parameters)) {
                throw new LogicException(sprintf(
                    'Route parameter name #[%s] from "%s" already in used.',
                    $parameterName,
                    $sourceDir . '/' . implode('/', array_slice($segments, $i + 1))
                ));
            }
            $parameters[$i] = $parameterName;
        }

        return $this->createParameterRoutes($sourceDir, $sourcePath, $parameters, []);
    }

    /**
     * @param string                $sourceDir
     * @param string                $sourcePath
     * @param array<int, string>    $parameters
     * @param array<string, string> $previous
     *
     * @return list<Route>
     */
    private function createParameterRoutes(string $sourceDir, string $sourcePath, array $parameters, array $previous): array
    {
        $routePath = substr($sourcePath, strlen($sourceDir));
        foreach ($this->documentExtensions as $ext) {
            if (str_ends_with($routePath, $ext)) {
                $routePath = substr($routePath, 0, strlen($ext) * -1);
                $routePath = $routePath . (str_ends_with($routePath, '/index') ? '.html' : '/index.html');
                break;
            }
        }
        if (empty($parameters)) {
            foreach ($previous as $name => $value) {
                $routePath = str_replace('/#[' . $name . ']/', '/' . $value . '/', $routePath);
            }
            return [new Route($routePath, $sourcePath, true, $previous)];
        }

        $i = key($parameters);
        $name = $parameters[$i];
        unset($parameters[$i]);

        $segments = explode('/', $routePath);
        $parameterHandlerPath = $sourceDir
            . implode('/', array_slice($segments, 0, $i + 1))
            . '/#param.php';
        $parameterResults = $this->getParameterResultsFromHandler($parameterHandlerPath, $previous);

        $output = [];
        foreach ($parameterResults as $parameterResult) {
            $previous[$name] = $parameterResult;
            $routes = $this->createParameterRoutes($sourceDir, $sourcePath, $parameters, $previous);
            $output = array_merge($output, $routes);
        }
        return $output;
    }

    /**
     * Get parameter results from the provided parameter handler PHP file path.
     *
     * @param string                $handlerPath
     * @param array<string, string> $previous
     *
     * @return list<string>
     */
    private function getParameterResultsFromHandler(string $handlerPath, array $previous): array
    {
        if (!file_exists($handlerPath)) {
            throw new UnexpectedValueException(sprintf(
                'Argument #1 $handlerPath must be a valid file, but "%s" given.',
                $handlerPath,
            ));
        }

        $hash = hash('xxh3', serialize(['handlerPath' => $handlerPath, 'previous' => $previous]));
        if (array_key_exists($hash, $this->parameterResults)) {
            return $this->parameterResults[$hash];
        }

        $func = include $handlerPath;
        $errorMessage = sprintf(
            'Argument #1 $handlerPath must return of type Closure():list<string>, but "%s" given.',
            get_debug_type($func),
        );
        if (!$func instanceof Closure) {
            throw new UnexpectedValueException($errorMessage);
        }
        $output = $func($previous);
        if (!is_array($output) || !array_is_list($output)) {
            throw new UnexpectedValueException($errorMessage);
        }
        foreach ($output as $value) {
            if (!is_string($value)) {
                throw new UnexpectedValueException($errorMessage);
            }
        }
        // @phpstan-ignore-next-line
        return $this->parameterResults[$hash] = array_values($output);
    }
}
