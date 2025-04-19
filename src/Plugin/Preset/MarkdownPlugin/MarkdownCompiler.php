<?php

declare(strict_types=1);

namespace Headercat\Statimate\Plugin\Preset\MarkdownPlugin;

use Headercat\Statimate\Compiler\CompileTarget;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;

final class MarkdownCompiler
{
    private static CommonMarkConverter $converter;

    /**
     * Compile the provided compile-target.
     *
     * @param CompileTarget $target Target to compile.
     *
     * @return string
     *
     * @throws CommonMarkException
     */
    public static function compileDocument(CompileTarget $target): string
    {
        return new self()->render($target->content);
    }

    /**
     * Get a CommonMarkConverter instance.
     *
     * @return CommonMarkConverter
     */
    private function getConverter(): CommonMarkConverter
    {
        if (!isset(self::$converter)) {
            self::$converter = new CommonMarkConverter();
        }
        return self::$converter;
    }

    /**
     * Render the Markdown content.
     *
     * @param string $content Content to render.
     *
     * @return string
     *
     * @throws CommonMarkException
     */
    public function render(string $content): string
    {
        return self::getConverter()->convert($content)->getContent();
    }
}
