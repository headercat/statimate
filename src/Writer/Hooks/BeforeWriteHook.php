<?php

declare(strict_types=1);

namespace Headercat\Statimate\Writer\Hooks;

use Closure;
use Headercat\Statimate\Hook\AbstractHook;

/**
 * @phpstan-type HookData array{ dest: string, content: string }
 * @extends AbstractHook<HookData, HookData>
 */
final class BeforeWriteHook extends AbstractHook
{
    /**
     * Subscribe the hook.
     *
     * @param Closure(HookData $data):HookData $subscriber Array of a destination-path and the content as an input and
     *                                                     its manipulation as an output.
     *
     * @return string
     */
    public static function subscribe(Closure $subscriber): string
    {
        return parent::subscribe($subscriber);
    }
}
