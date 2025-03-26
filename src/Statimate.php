<?php

declare(strict_types=1);

namespace Headercat\Statimate;

use Closure;
use Headercat\Statimate\Supports\Directory;
use Headercat\Statimate\Supports\Immutable;
use RuntimeException;
use Throwable;

final class Statimate
{
    use Immutable;

    /**
     * @var string Root directory which contains the configuration file.
     */
    private(set) string $rootDir;

    /**
     * @var string Route directory.
     */
    private(set) string $routeDir;

    /**
     * @var string Build directory.
     */
    private(set) string $buildDir;

    /**
     * @var list<string> File extensions that should be treated as documentation.
     */
    private(set) array $documentExtensions = ['blade.php', 'html', 'md'];

    /**
     * @var list<string> File extensions that should be excluded.
     */
    private(set) array $excludedExtensions = ['php'];

    /**
     * @var list<Closure(string, array<string, string>):(string|false)> Renderers to render the file.
     */
    private(set) array $renderers = [];

    /**
     * Constructor.
     *
     * @param string|null $rootDir Root directory. Automatically detected if it left null.
     */
    public function __construct(string|null $rootDir = null)
    {
        $this->rootDir = Directory::trim($rootDir ?? $this->getAutoDetectedRootDir());
        try {
            $this->routeDir = Directory::trim($this->rootDir . '/routes');
        } catch (Throwable) {
        }
    }

    /**
     * Create a new instance with the route directory.
     *
     * @param string $routeDir Route directory.
     *
     * @return self Instance with the new route directory.
     */
    public function withRouteDir(string $routeDir): self
    {
        return $this->with('routeDir', Directory::trim($routeDir));
    }

    /**
     * Create a new instance with the build directory.
     *
     * @param string $buildDir Build directory.
     *
     * @return self Instance with the new build directory.
     */
    public function withBuildDir(string $buildDir): self
    {
        return $this->with('buildDir', $buildDir);
    }

    /**
     * Create a new instance with the document extensions.
     *
     * @param list<string> $documentExtensions File extensions that should be treated as documentation.
     *
     * @return self Instance with the new document extensions.
     */
    public function withDocumentExtensions(array $documentExtensions): self
    {
        $documentExtensions = array_map(
            fn(string $v) => str_starts_with($v, '.') ? substr($v, 1) : $v,
            array_unique($documentExtensions)
        );
        return $this->with('documentExtensions', $documentExtensions);
    }

    /**
     * Create a new instance with the excluded extensions.
     *
     * @param list<string> $excludedExtensions File extensions that should be excluded.
     * @param bool         $force              Forced not to append 'php' if $excludedExtensions does not include it.
     *
     * @return self Instance with the new excluded extensions.
     */
    public function withExcludedExtensions(array $excludedExtensions, bool $force = false): self
    {
        $excludedExtensions = array_map(
            fn(string $v) => str_starts_with($v, '.') ? substr($v, 1) : $v,
            array_unique($excludedExtensions)
        );
        if (!$force && !in_array('php', $excludedExtensions)) {
            $excludedExtensions[] = 'php';
        }
        return $this->with('excludedExtensions', $excludedExtensions);
    }

    /**
     * Create a new instance with the renderer added.
     *
     * @param Closure(string, array<string, string>):(string|false) $renderer Renderer to render the file. Expects a
     *                                                                        closure that receives the file name and
     *                                                                        placeholders as a parameter and returns
     *                                                                        the rendered output if the file is able
     *                                                                        to render, false otherwise.
     *
     * @return self
     */
    public function withAddedRenderer(Closure $renderer): self
    {
        $renderers = array_merge($this->renderers, [$renderer]);
        return $this->with('renderers', $renderers);
    }

    /**
     * Get automatically detected root directory.
     *
     * @return string Automatically detected root directory.
     */
    private function getAutoDetectedRootDir(): string
    {
        $backtrace = debug_backtrace();
        foreach ($backtrace as $trace) {
            if (($trace['class'] ?? null) !== self::class) {
                continue;
            }
            if ($trace['function'] !== '__construct') {
                continue;
            }
            if (!isset($trace['file'])) {
                throw new RuntimeException(
                    'Cannot determine root directory. Try to pass the root directory directly.'
                );
            }
            return dirname($trace['file']);
        }
        throw new RuntimeException(
            'Cannot determine root directory. Try to pass the root directory directly.'
        );
    }
}
