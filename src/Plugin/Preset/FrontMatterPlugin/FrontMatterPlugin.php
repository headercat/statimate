<?php

declare(strict_types=1);

namespace Headercat\Statimate\Plugin\Preset\FrontMatterPlugin;

use Headercat\Statimate\Compiler\CompileTarget;
use Headercat\Statimate\Compiler\Hooks\AfterCompileHook;
use Headercat\Statimate\Compiler\Hooks\BeforeCompileHook;
use Headercat\Statimate\Config\StatimateConfig;
use Headercat\Statimate\Plugin\PluginInterface;
use Headercat\Statimate\Router\Hooks\AfterCollectRouteHook;

final class FrontMatterPlugin implements PluginInterface
{
    private const string FRONT_MATTER_REGEX = '/^---\n(.*?)\n---\n/';
    public function register(StatimateConfig $config): void
    {
        AfterCollectRouteHook::subscribe(function (array $routes) {
            foreach ($routes as $route) {
                $content = file_get_contents($route->sourcePath);
                if (!$content) {
                    continue;
                }
                $content = preg_replace(self::FRONT_MATTER_REGEX, '', $content, 1);
                $route->extras['content'] = $content;
            }
            return $routes;
        });

        BeforeCompileHook::subscribe(function (CompileTarget $target) {
            $output = preg_replace(self::FRONT_MATTER_REGEX, '', $target->content, 1);
            assert(is_string($output));
            $target->content = $output;
            return $target;
        });
        AfterCompileHook::subscribe(function (string $compiledContent) {
            $output = preg_replace(self::FRONT_MATTER_REGEX, '', $compiledContent, 1);
            assert(is_string($output));
            return $output;
        });
    }
}
