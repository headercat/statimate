<?php

declare(strict_types=1);

namespace Headercat\Statimate\Writer\Hooks;

use Closure;
use Headercat\Statimate\Hook\AbstractHook;

/**
 * @phpstan-type HookData array{ dest: string, from: string }
 * @extends AbstractHook<HookData, HookData>
 */
final class BeforeCopyHook extends AbstractHook
{
    /**
     * Subscribe the hook.
     *
     * @param Closure(HookData $data):HookData $subscriber Array of a destination-path and from-path as an input and
     *                                                     its manipulation as an output.
     *
     * @return string
     */
    public static function subscribe(Closure $subscriber): string
    {
        return parent::subscribe($subscriber);
    }
}
