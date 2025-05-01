<?php

declare(strict_types=1);

namespace Headercat\Statimate\Plugin\Preset\FrontMatterPlugin;

use Headercat\Statimate\Compiler\CompileTarget;
use Headercat\Statimate\Compiler\Hooks\AfterCompileHook;
use Headercat\Statimate\Compiler\Hooks\BeforeCompileHook;
use Headercat\Statimate\Config\StatimateConfig;
use Headercat\Statimate\Plugin\PluginInterface;
use Headercat\Statimate\Router\Hooks\AfterCollectRouteHook;
use Symfony\Component\Yaml\Yaml;

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
                $content = preg_match(self::FRONT_MATTER_REGEX, $content, $matches) ? $matches[1] : '';
                if (is_array($output = Yaml::parse($content))) {
                    $route->extras = $output; // @phpstan-ignore-line
                }
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
