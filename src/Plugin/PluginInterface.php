<?php

declare(strict_types=1);

namespace Headercat\Statimate\Plugin;

use Headercat\Statimate\Config\StatimateConfig;

interface PluginInterface
{
    /**
     * Register the plugin.
     *
     * @param StatimateConfig $config
     *
     * @return void
     */
    public function register(StatimateConfig $config): void;
}
