<?php

declare(strict_types=1);

namespace Headercat\Statimate\Plugin\Preset\PaginationPlugin;

use Headercat\Statimate\Config\StatimateConfig;
use Headercat\Statimate\Plugin\PluginInterface;

final class PaginationPlugin implements PluginInterface
{
    public function register(StatimateConfig $config): void
    {
        // I know it's stupid code, but...
        // I just don't want the user's IDE to autocomplete any methods or properties that can access to P::$config.

        $setter = function () use ($config) {
            self::$config = $config; // @phpstan-ignore-line
        };
        $setter = $setter->bindTo(new Pagination(), Pagination::class);
        $setter();
    }
}
