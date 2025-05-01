<?php

declare(strict_types=1);

namespace Headercat\Statimate\Compiler;

use Headercat\Statimate\Compiler\Hooks\AfterCompileHook;
use Headercat\Statimate\Compiler\Hooks\BeforeCompileHook;
use Headercat\Statimate\Config\StatimateConfig;
use Headercat\Statimate\Router\Route;
use RuntimeException;
use UnexpectedValueException;

final readonly class Compiler
{
    /**
     * Constructor.
     *
     * @param StatimateConfig $config Statimate configuration.
     */
    public function __construct(private StatimateConfig $config)
    {
    }

    /**
     * Compile a template of the provided Route.
     *
     * @param Route $route Route to compile the template.
     *
     * @return string
     */
    public function compile(Route $route): string
    {
        if (!$route->isDocument) {
            throw new UnexpectedValueException(
                'Argument #1 $route must be of type Route,'
                . ' and its property Route::$isDocument must be true, but false given.'
            );
        }

        $content = '';
        foreach ($this->getCompileTargetPaths($route) as $path) {
            $content = $this->getCompiledContent($path, [
                'route' => $route,
                'params' => $route->parameters,
                'content' => $content,
            ]);
        }
        return $content;
    }

    /**
     * Get compile-target paths, especially the origin route path, and it's layout files.
     *
     * @param Route $route Route to get the compile target paths.
     *
     * @return list<string>
     */
    private function getCompileTargetPaths(Route $route): array
    {
        $targets = [$path = $route->sourcePath];
        while (true) {
            $path = dirname($path);
            if (!str_starts_with($path, $this->config->routeDir)) {
                break;
            }
            foreach ($this->config->documentCompilers as $ext => $_) {
                $layoutPath = $path . '/#layout' . $ext;
                if (file_exists($layoutPath)) {
                    $targets[] = $layoutPath;
                    break;
                }
            }
        }
        return $targets;
    }

    /**
     * Compile the content with a proper document compiler.
     *
     * @param string               $path Path to compile.
     * @param array<string, mixed> $vars Variables to pass.
     *
     * @return string
     */
    private function getCompiledContent(string $path, array $vars): string
    {
        $compileTarget = new CompileTarget($path, $vars, $this->config);
        foreach ($this->config->documentCompilers as $ext => $handler) {
            if (!str_ends_with($path, $ext)) {
                continue;
            }
            $compileTarget = BeforeCompileHook::dispatch($compileTarget);
            $output = $handler($compileTarget);
            if (!is_string($output)) {
                throw new RuntimeException(sprintf(
                    'Document compiler for "%s" must return type string, but %s given.',
                    $ext,
                    get_debug_type($output),
                ));
            }
            return AfterCompileHook::dispatch($output);
        }
        return AfterCompileHook::dispatch($compileTarget->content);
    }
}
