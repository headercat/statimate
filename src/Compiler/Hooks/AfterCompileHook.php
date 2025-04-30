<?php

declare(strict_types=1);

namespace Headercat\Statimate\Compiler\Hooks;

use Closure;
use Headercat\Statimate\Hook\AbstractHook;

/**
 * @extends AbstractHook<string, string>
 */
final class AfterCompileHook extends AbstractHook
{
    /**
     * Subscribe the hook.
     *
     * @param Closure(string $compiledContent):string $subscriber Compiled content as an input and manipulated content
     *                                                as an output.
     *
     * @return string Identifier of the subscriber.
     */
    public static function subscribe(Closure $subscriber): string
    {
        return parent::subscribe($subscriber);
    }
}
