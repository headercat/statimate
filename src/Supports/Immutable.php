<?php

declare(strict_types=1);

namespace Headercat\Statimate\Supports;

/**
 * Trait that implements an immutable class.
 */
trait Immutable
{
    /**
     * Create a new instance with $value set to $object->$key.
     *
     * @param string $key   Property key to replace the previous value.
     * @param mixed                     $value Value to replace.
     *
     * @return static
     */
    private function with(string $key, mixed $value): static
    {
        assert(property_exists($this, $key));
        $obj = clone $this;
        $obj->{$key} = $value;
        return $obj;
    }
}
