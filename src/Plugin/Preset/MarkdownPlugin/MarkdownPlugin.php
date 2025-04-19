<?php

declare(strict_types=1);

namespace Headercat\Statimate\Plugin\Preset\MarkdownPlugin;

use Headercat\Statimate\Config\StatimateConfig;
use Headercat\Statimate\Plugin\PluginInterface;

final class MarkdownPlugin implements PluginInterface
{
    public function register(StatimateConfig $config): void
    {
        $config->addDocumentCompiler('.md', MarkdownCompiler::compileDocument(...));
    }
}
