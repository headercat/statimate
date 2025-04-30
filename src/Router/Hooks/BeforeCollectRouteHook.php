<?php

declare(strict_types=1);

namespace Headercat\Statimate\Router\Hooks;

use Closure;
use Headercat\Statimate\Hook\AbstractHook;

/**
 * @extends AbstractHook<string, string>
 */
final class BeforeCollectRouteHook extends AbstractHook
{
    /**
     * Subscribe the hook.
     *
     * @param Closure(string $sourceDir):string $subscriber Source-dir as an input and manipulated source-path as an
     *                                                      output. All source-dirs do not have any guarantee that is
     *                                                      some of a real-existence path. But the final output
     *                                                      source-dir should be a real-existence path, otherwise the
     *                                                      build process will be failed.
     *
     * @return string
     */
    public static function subscribe(Closure $subscriber): string
    {
        return parent::subscribe($subscriber);
    }
}
