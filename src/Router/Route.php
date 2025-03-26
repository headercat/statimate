<?php

declare(strict_types=1);

namespace Headercat\Statimate\Router;

use Headercat\Statimate\Supports\Immutable;

final class Route
{
    use Immutable;

    /**
     * @var string Absolute path of the original source file.
     */
    private(set) string $sourcePath = '';

    /**
     * @var string Relative path that real users can access to.
     */
    private(set) string $routePath = '';

    /**
     * @var bool Whether if the route is for documentation.
     */
    private(set) bool $isDocument = false;

    /**
     * @var array<string, string> Placeholders.
     */
    private(set) array $placeholders = [];

    /**
     * @var list<string> Layout files.
     */
    private(set) array $layouts = [];

    /**
     * Create a new instance with the source file path.
     *
     * @param string $sourcePath Absolute path of the original source file.
     *
     * @return self Instance with the new source path.
     */
    public function withSourcePath(string $sourcePath): self
    {
        return $this->with('sourcePath', $sourcePath);
    }

    /**
     * Create a new instance with the route path.
     *
     * @param string $routePath Relative path that real users can access to.
     *
     * @return self Instance with the new route path.
     */
    public function withRoutePath(string $routePath): self
    {
        return $this->with('routePath', $routePath);
    }

    /**
     * Create a new instance with the documentation flag.
     *
     * @param bool $isDocument Whether if the route is for documentation.
     *
     * @return self Instance with the new documentation flag.
     */
    public function withIsDocument(bool $isDocument): self
    {
        return $this->with('isDocument', $isDocument);
    }

    /**
     * Create a new instance with placeholders.
     *
     * @param array<string, string> $placeholders Placeholders.
     *
     * @return self Instance with the new placeholders.
     */
    public function withPlaceholders(array $placeholders): self
    {
        return $this->with('placeholders', $placeholders);
    }

    /**
     * Create a new instance with layout files.
     *
     * @param list<string> $layouts Layout files.
     *
     * @return self Instance with the new layout files.
     */
    public function withLayouts(array $layouts): self
    {
        return $this->with('layouts', $layouts);
    }
}
