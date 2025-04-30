<?php

declare(strict_types=1);

namespace Headercat\Statimate\Compiler\Hooks;

use Closure;
use Headercat\Statimate\Compiler\CompileTarget;
use Headercat\Statimate\Hook\AbstractHook;

/**
 * @extends AbstractHook<CompileTarget, CompileTarget>
 */
final class BeforeCompileHook extends AbstractHook
{
    /**
     * Subscribe the hook.
     *
     * @param Closure(CompileTarget $route):CompileTarget $subscriber Compile-target as an input and manipulated
     *                                                    compile-target as an output.
     *
     * @return string
     */
    public static function subscribe(Closure $subscriber): string
    {
        return parent::subscribe($subscriber);
    }
}
