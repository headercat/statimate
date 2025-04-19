<?php

declare(strict_types=1);

namespace Headercat\Statimate\Helper;

use Closure;
use Headercat\Statimate\Config\StatimateConfig;
use Headercat\Statimate\Router\Route;
use Headercat\Statimate\Router\RouteCollector;
use UnexpectedValueException;

final class Pagination
{
    /**
     * @var StatimateConfig Statimate configuration.
     */
    private static StatimateConfig $config;

    /**
     * @var list<Route> All routes collected from the globally-configured route directory.
     */
    private static array $routes;

    /**
     * @var array<string, list<list<Route>>> Map of the pagination key and its document routes.
     */
    private static array $keyRoutes = [];

    /**
     * Initialize the pagination helper.
     *
     * @param StatimateConfig $config Statimate configuration.
     *
     * @return void
     */
    public static function init(StatimateConfig $config): void
    {
        self::$config = $config;
    }

    /**
     * Get the pagination param function.
     *
     * @param string                          $key
     * @param string                          $baseDir Base directory to get the page count.
     * @param positive-int                    $perPage Document route count per page.
     * @param (Closure(Route):bool)|null      $filterBy
     * @param (Closure(Route,Route):int)|null $orderBy
     *
     * @return Closure():list<string>
     */
    public static function getParams(
        string $key, string $baseDir, int $perPage = 20, Closure|null $filterBy = null, Closure|null $orderBy = null,
    ): Closure
    {
        $chunkedRoutes = self::getChunkedRoutes($key, $baseDir, $perPage, $filterBy, $orderBy);
        return fn() => array_map(strval(...), range(1, count($chunkedRoutes)));
    }

    /**
     * Get the document routes of the provided page information.
     *
     * @param string     $key  Pagination key which has been registered from getParams.
     * @param string|int $page Current page looking for.
     *
     * @return list<Route>
     */
    public static function getPageDocumentRoutes(string $key, string|int $page): array
    {
        $page = max((int) $page, 1);
        return (self::$keyRoutes[$key] ?? [])[$page - 1] ?? [];
    }

    /**
     * Get routes inside the provided key, base directory and per page.
     *
     * @param string                          $key
     * @param string                          $baseDir Base directory to get the page count.
     * @param positive-int                    $perPage Document route count per page.
     * @param (Closure(Route):bool)|null      $filterBy
     * @param (Closure(Route,Route):int)|null $orderBy
     *
     * @return list<list<Route>>
     */
    private static function getChunkedRoutes(
        string $key, string $baseDir, int $perPage = 20, Closure|null $filterBy = null, Closure|null $orderBy = null,
    ): array
    {
        if (isset(self::$keyRoutes[$key])) {
            return self::$keyRoutes[$key];
        }

        $baseDir = realpath($baseDir);
        if (!$baseDir) {
            throw new UnexpectedValueException(sprintf(
                'Argument #2 $baseDir must be of type Route,'
                . ' and its property Route::$sourcePath must be a valid, accessible path, but "%s" given.',
                $baseDir,
            ));
        }
        if (!isset(self::$routes)) {
            self::$routes = new RouteCollector(self::$config)
                ->collect(self::$config->routeDir, true);
        }

        $documentRoutes = array_values(array_filter(
            self::$routes,
            fn(Route $route) => $route->isDocument && str_starts_with($route->sourcePath, $baseDir),
        ));
        if ($filterBy) {
            $documentRoutes = array_values(array_filter($documentRoutes, $filterBy));
        }
        usort($documentRoutes, $orderBy ?? fn(Route $a, Route $b) => $b->route <=> $a->route);
        return self::$keyRoutes[$key] = array_chunk($documentRoutes, $perPage);
    }
}
