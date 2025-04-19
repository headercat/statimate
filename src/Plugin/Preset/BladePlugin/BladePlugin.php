<?php

declare(strict_types=1);

namespace Headercat\Statimate\Plugin\Preset\BladePlugin;

use Headercat\Statimate\Config\StatimateConfig;
use Headercat\Statimate\Plugin\PluginInterface;

final class BladePlugin implements PluginInterface
{
    public function register(StatimateConfig $config): void
    {
        $config->addDocumentCompiler('.blade.php', BladeCompiler::compileDocument(...));
    }
}
