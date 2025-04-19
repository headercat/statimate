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
     * @var array<string, list<Route>> Map of the base directory and its document routes.
     */
    private static array $documentRoutes = [];

    /**
     * @var array<string, array{ baseDir: string, perPage: int }> Map of the pagination key and its configuration.
     */
    private static array $paginationConfig = [];

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
     * @param string       $baseDir Base directory to get the page count.
     * @param positive-int $perPage Document route count per page.
     *
     * @return Closure():list<string>
     */
    public static function getParams(string $key, string $baseDir, int $perPage = 20): Closure
    {
        self::$paginationConfig[$key] = ['baseDir' => $baseDir, 'perPage' => $perPage];
        $routes = self::getRoutes($baseDir);
        $pageCount = max(count(array_chunk($routes, $perPage)), 1);

        return fn() => array_map(strval(...), range(1, $pageCount));
    }

    /**
     * Get the document routes of the provided page information.
     *
     * @param string                          $key     Pagination key which has been registered from getParams.
     * @param string|int                      $page    Current page looking for.
     * @param (Closure(Route,Route):int)|null $orderBy Closure to sort the document routes.
     *
     * @return list<Route>
     */
    public static function getPageDocumentRoutes(string $key, string|int $page, Closure|null $orderBy = null): array
    {
        $config = self::$paginationConfig[$key] ?? null;
        if (!$config) {
            throw new UnexpectedValueException(sprintf(
                'Argument #1 $key must be a valid pagination key, but "%s" given.',
                $key,
            ));
        }
        ['baseDir' => $baseDir, 'perPage' => $perPage] = $config;

        $page = max((int)$page, 1);
        $routes = self::getRoutes($baseDir);
        usort($routes, $orderBy ?? fn(Route $a, Route $b) => $b->route <=> $a->route);
        return array_slice($routes, ($page - 1) * $perPage, $perPage);
    }

    /**
     * Get routes inside the provided base directory.
     *
     * @param string $baseDir Base directory to find the routes.
     *
     * @return list<Route>
     */
    private static function getRoutes(string $baseDir): array
    {
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
        if (!isset(self::$documentRoutes[$baseDir])) {
            self::$documentRoutes[$baseDir] = array_values(
                array_filter(
                    self::$routes,
                    fn(Route $route) => $route->isDocument && str_starts_with($route->sourcePath, $baseDir),
                )
            );
        }
        return self::$documentRoutes[$baseDir];
    }
}
