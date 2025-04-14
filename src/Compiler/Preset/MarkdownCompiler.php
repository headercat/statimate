<?php

declare(strict_types=1);

namespace Headercat\Statimate\Compiler\Preset;

use Headercat\Statimate\Compiler\CompileTarget;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Exception\CommonMarkException;

final class MarkdownCompiler
{
    private static CommonMarkConverter $converter;

    /**
     * Compile the provided compile target.
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
     * Get CommonMarkConverter instance.
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
     * Render the markdown content.
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
