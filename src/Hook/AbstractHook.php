<?php

declare(strict_types=1);

namespace Headercat\Statimate\Hook;

use Closure;
use LogicException;

/**
 * @template TInput
 * @template TOutput
 */
abstract class AbstractHook
{
    /**
     * @var array<string, array<string, Closure>> Map of the hook name and its listeners.
     */
    private static array $subscribers = [];

    /**
     * Subscribe the hook.
     *
     * @param Closure(TInput):TOutput $subscriber
     *
     * @return string Identifier of the subscriber.
     */
    public static function subscribe(Closure $subscriber): string
    {
        if (static::class === self::class) {
            throw new LogicException('Cannot subscribe to the abstract class "' . static::class . '".');
        }
        if (!array_key_exists(static::class, self::$subscribers)) {
            self::$subscribers[static::class] = [];
        }

        $name = spl_object_hash($subscriber);
        self::$subscribers[static::class][$name] = $subscriber;
        return $name;
    }

    /**
     * Dispatch the event.
     *
     * @param TInput $data Data to dispatch.
     *
     * @return TOutput
     */
    public static function dispatch(mixed $data): mixed
    {
        $data = is_object($data) ? clone $data : $data;
        foreach (self::$subscribers[static::class] ?? [] as $subscriber) {
            $data = $subscriber($data);
        }
        return $data;
    }
}
