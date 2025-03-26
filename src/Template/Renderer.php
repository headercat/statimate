<?php

declare(strict_types=1);

namespace Headercat\Statimate\Template;

use eftec\bladeone\BladeOne;
use Parsedown;
use RuntimeException;
use Throwable;
use Closure;

final class Renderer
{
    /**
     * Constructor.
     *
     * @param string                                                      $rootDir   Root directory of the app.
     * @param list<Closure(string, array<string, string>):(string|false)> $renderers Renderers to render the file.
     */
    public function __construct(private readonly string $rootDir, private array $renderers)
    {
        $this->renderers = array_merge(
            $this->renderers,
            [
                $this->renderBlade(...),
                $this->renderMarkdown(...),
            ]
        );
    }

    /**
     * Render file.
     *
     * @param string                $sourcePath
     * @param array<string, string> $placeholders
     *
     * @return string
     */
    public function render(string $sourcePath, array $placeholders): string
    {
        foreach ($this->renderers as $renderer) {
            $output = $renderer($sourcePath, $placeholders);
            if ($output !== false) {
                return $output;
            }
        }
        $output = file_get_contents($sourcePath);
        if ($output === false) {
            throw new RuntimeException('Cannot read file "' . $sourcePath . '".');
        }
        return $output;
    }

    /**
     * Render .blade.php file.
     *
     * @param string                $sourcePath   Absolute path of the original source file.
     * @param array<string, string> $placeholders Placeholders.
     * @param bool                  $force        Force to render even the extension is not compatible.
     *
     * @return string|false
     */
    private function renderBlade(string $sourcePath, array $placeholders, bool $force = false): string|false
    {
        if (!$force && !str_ends_with($sourcePath, '.blade.php') && !str_ends_with($sourcePath, '.html')) {
            return false;
        }
        $viewName = substr($sourcePath, strlen($this->rootDir) + 1);

        static $bladeOne = null;
        if ($bladeOne === null) {
            $bladeOneCacheDir = sys_get_temp_dir() . '/headercat/statimate/renderer/bladeOne';
            @mkdir($bladeOneCacheDir, 0777, true);

            $bladeOne = new BladeOne($this->rootDir, $bladeOneCacheDir);
        }
        try {
            /** @var BladeOne $bladeOne */
            $_GET = $placeholders;
            return $bladeOne->run($viewName);
        } catch (Throwable) {
            throw new RuntimeException('Cannot render file "' . $sourcePath . '".');
        }
    }

    /**
     * Render .md file.
     *
     * @param string                $sourcePath   Absolute path of the original source file.
     * @param array<string, string> $placeholders Placeholders.
     *
     * @return string|false
     * @noinspection PhpRedundantVariableDocTypeInspection
     */
    private function renderMarkdown(string $sourcePath, array $placeholders): string|false
    {
        if (!str_ends_with($sourcePath, '.md')) {
            return false;
        }
        $output = $this->renderBlade($sourcePath, $placeholders, true);
        assert(is_string($output));

        static $parseDown = new Parsedown();

        /** @var Parsedown $parseDown */
        $output = @$parseDown->text($output);
        if (!is_string($output)) {
            throw new RuntimeException('Cannot render file "' . $sourcePath . '".');
        }
        return $output;
    }
}
