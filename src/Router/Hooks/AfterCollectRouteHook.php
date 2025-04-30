<?php

declare(strict_types=1);

namespace Headercat\Statimate\Router\Hooks;

use Closure;
use Headercat\Statimate\Hook\AbstractHook;
use Headercat\Statimate\Router\Route;

/**
 * @extends AbstractHook<list<Route>, list<Route>>
 */
final class AfterCollectRouteHook extends AbstractHook
{
    /**
     * Subscribe the hook.
     *
     * @param Closure(list<Route> $routes):list<Route> $subscriber List of routes as an input and manipulated list of
     *                                                             routes as an output. Sorting is not guaranteed, but
     *                                                             it's highly recommended to return sorted values.
     *                                                             The final returned value must not have duplicate
     *                                                             paths.
     *
     * @return string
     */
    public static function subscribe(Closure $subscriber): string
    {
        return parent::subscribe($subscriber);
    }
}
