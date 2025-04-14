<?php

declare(strict_types=1);

namespace Headercat\Statimate\Router;

final readonly class Route
{
    /**
     * Constructor.
     *
     * @param string                $route
     * @param string                $sourcePath
     * @param bool                  $isDocument
     * @param array<string, string> $parameters
     */
    public function __construct(
        public string $route,
        public string $sourcePath,
        public bool   $isDocument,
        public array  $parameters,
    )
    {
    }
}
