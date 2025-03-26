<?php

declare(strict_types=1);

namespace Headercat\Statimate\Router;

use Headercat\Statimate\Supports\Directory;
use LogicException;
use SplFileInfo;
use UnexpectedValueException;

final class RouteCollector
{
    /**
     * @var array<string, array<string, list<string>>> Placeholder handled outputs.
     */
    private array $placeholderOutputs = [];

    /**
     * Constructor.
     *
     * @param string       $routeDir           Route directory.
     * @param list<string> $documentExtensions File extensions that should be treated as documentation.
     * @param list<string> $excludedExtensions File extensions that should be excluded.
     */
    public function __construct(
        private readonly string $routeDir,
        private readonly array  $documentExtensions,
        private readonly array  $excludedExtensions,
    )
    {
    }

    /**
     * Collect routes from the route directory.
     *
     * @return list<Route> List of the collected Route instances.
     */
    public function collect(): array
    {
        $iterator = Directory::createRecursiveIterator($this->routeDir);
        $output = [];
        foreach ($iterator as $file) {
            assert($file instanceof SplFileInfo);
            if ($file->isDir()) {
                continue;
            }
            if (in_array($file->getExtension(), $this->excludedExtensions)) {
                $nonExcluded = false;
                foreach ($this->documentExtensions as $extension) {
                    if (str_ends_with($file->getFilename(), '.' . $extension)) {
                        $nonExcluded = true;
                        break;
                    }
                }
                if (!$nonExcluded) {
                    continue;
                }
            }
            $output = array_merge($output, $this->createRoute($file));
        }
        return $output;
    }

    /**
     * Create Route instances from SplFileInfo.
     *
     * @param SplFileInfo $file File information instance.
     *
     * @return list<Route> Route instance.
     */
    private function createRoute(SplFileInfo $file): array
    {
        $ext = $file->getExtension();
        foreach ($this->documentExtensions as $extension) {
            if (str_ends_with($file->getFilename(), '.' . $extension)) {
                $ext = $extension;
                break;
            }
        }

        $isDocument = in_array($ext, $this->documentExtensions);
        if ($isDocument && $file->getFilename() === '@.' . $ext) {
            return [];
        }

        $sourcePath = $file->getPathname();
        $routePath = $this->getRoutePathFromSourcePath($sourcePath, $ext);
        $route = new Route()
            ->withSourcePath($sourcePath)
            ->withRoutePath($routePath)
            ->withIsDocument($isDocument);

        if (!$isDocument) {
            return [$route];
        }
        $placeholders = $this->getPlaceholdersFromRoutePath($routePath);
        return $this->createPlaceholderRoutes($route, $placeholders);
    }

    /**
     * Compute the route path from the route directory and the source path.
     *
     * @param string $sourcePath Absolute path of the original source file.
     * @param string $extension  File extension.
     *
     * @return string Relative path that real user can access to.
     */
    private function getRoutePathFromSourcePath(string $sourcePath, string $extension): string
    {
        if (!str_starts_with($sourcePath, $this->routeDir)) {
            throw new LogicException(sprintf(
                'The source path "%s" is not a file inside the route directory "%s". Do not use features like symlink.',
                $sourcePath,
                $this->routeDir
            ));
        }
        $trimmed = substr($sourcePath, strlen($this->routeDir));
        if (in_array($extension, $this->documentExtensions)) {
            $extensionTrimmed = substr($trimmed, 0, strlen($trimmed) - strlen($extension) - 1);
            if (str_ends_with($extensionTrimmed, '/index')) {
                return $extensionTrimmed . '.html';
            }
            return $extensionTrimmed . '/index.html';
        }
        return $trimmed;
    }

    /**
     * Get placeholders from the route path.
     *
     * @param string $routePath Relative path that real user can access to.
     *
     * @return list<array{ placeholder: string, handlerFile: string }>
     */
    private function getPlaceholdersFromRoutePath(string $routePath): array
    {
        if (!str_contains($routePath, '[')) {
            return [];
        }
        $segments = explode('/', $routePath);

        $placeholders = [];
        $placeholderNames = [];
        foreach ($segments as $i => $segment) {
            if (!str_starts_with($segment, '[') || !str_ends_with($segment, ']')) {
                continue;
            }

            $placeholder = substr($segment, 1, -1);
            if (in_array($placeholder, $placeholderNames)) {
                throw new LogicException('Duplicated placeholder name "' . $placeholder . '" detected.');
            }
            $placeholderNames[] = $placeholder;

            $handlerFile = implode('/', array_slice($segments, 0, $i + 1)) . '/?.php';
            $absoluteHandlerFile = $this->routeDir . $handlerFile;
            if (!file_exists($absoluteHandlerFile)) {
                continue;
            }
            $placeholders[] = ['placeholder' => $placeholder, 'handlerFile' => $absoluteHandlerFile];
        }
        return $placeholders;
    }

    /**
     * Create routes with placeholder.
     *
     * @param Route                                                   $route        Base Route instance.
     * @param list<array{ placeholder: string, handlerFile: string }> $placeholders Placeholder information.
     *
     * @return list<Route> Route instances.
     */
    private function createPlaceholderRoutes(Route $route, array $placeholders): array
    {
        $route = $route->withLayouts($this->getLayoutsFromSourcePath($route->sourcePath));
        $targetPlaceholder = $placeholders[count($route->placeholders)] ?? null;
        if (!$targetPlaceholder) {
            return [$route];
        }

        $name = $targetPlaceholder['placeholder'];
        $handlerOutput = $this->getPlaceholderHandlerOutput($targetPlaceholder['handlerFile'], $route->placeholders);

        $output = [];
        foreach ($handlerOutput as $value) {
            $routePath = substr_replace(
                $route->routePath,
                $value,
                strpos($route->routePath, $placeholder = '[' . $name . ']'), // @phpstan-ignore-line
                strlen($placeholder)
            );
            $newRoute = $route
                ->withPlaceholders([...$route->placeholders, $name => $value])
                ->withRoutePath($routePath);
            $routes = $this->createPlaceholderRoutes($newRoute, $placeholders);
            $output = array_merge($output, $routes);
        }
        return $output;
    }

    /**
     * Get output from placeholder handler file.
     *
     * @param string                $handlerFile Handler file of the placeholder.
     * @param array<string, string> $handled     Outputs from previous placeholders.
     *
     * @return list<string> Output of the current placeholder.
     */
    private function getPlaceholderHandlerOutput(string $handlerFile, array $handled = []): array
    {
        $previousHash = hash('xxh3', serialize($handled));
        if (array_key_exists($handlerFile, $this->placeholderOutputs)) {
            $handlerFileCache = $this->placeholderOutputs[$handlerFile];
            if (array_key_exists($previousHash, $handlerFileCache)) {
                return $handlerFileCache[$previousHash];
            }
        } else {
            $this->placeholderOutputs[$handlerFile] = [];
        }

        $scoped = function () use ($handlerFile, $handled) {
            $_GET = $handled;
            $output = include $handlerFile;
            if (!is_array($output)) {
                throw new UnexpectedValueException(sprintf(
                    'Placeholder handler must return an list<string>, but %s given.',
                    get_debug_type($output),
                ));
            }
            array_walk($output, function (&$value) use ($output) {
                if (is_int($value) || is_float($value)) {
                    $value = (string)$value;
                }
                if (!is_string($value)) {
                    throw new UnexpectedValueException(sprintf(
                        'Placeholder handler must return an list<string>, but %s given.',
                        get_debug_type($output),
                    ));
                }
            });
            return $output;
        };
        /** @var list<string> $output */
        $output = $scoped->bindTo(null)();
        return $this->placeholderOutputs[$handlerFile][$previousHash] = $output;
    }

    /**
     * Get layout files from the source path.
     *
     * @param string       $sourcePath Absolute path of the original source file.
     * @param list<string> $previous   Previous memoized layout files from this method.
     *
     * @return list<string> Ordered list of layout files.
     */
    private function getLayoutsFromSourcePath(string $sourcePath, array $previous = []): array
    {
        $dir = dirname($sourcePath);
        if (!str_starts_with($dir, $this->routeDir)) {
            return $previous;
        }
        foreach ($this->documentExtensions as $extension) {
            $layoutFile = $dir . '/@.' . $extension;
            if (is_file($layoutFile)) {
                $previous[] = $layoutFile;
            }
        }
        return $this->getLayoutsFromSourcePath($dir, $previous);
    }
}
