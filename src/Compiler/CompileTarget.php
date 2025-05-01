<?php

declare(strict_types=1);

namespace Headercat\Statimate\Compiler;

use Headercat\Statimate\Config\StatimateConfig;
use UnexpectedValueException;

final class CompileTarget
{
    public string $content;

    /**
     * Constructor.
     *
     * @param string               $path   Path to compile.
     * @param array<string, mixed> $vars   Variables to pass.
     * @param StatimateConfig      $config Statimate configuration.
     */
    public function __construct(
        public string          $path,
        public array           $vars,
        public StatimateConfig $config,
    )
    {
        try {
            if (!file_exists($this->path)) {
                throw new UnexpectedValueException();
            }
            $content = file_get_contents($this->path);
            if ($content === false) {
                throw new UnexpectedValueException();
            }
            $this->content = $content;
        } catch (UnexpectedValueException) {
            throw new UnexpectedValueException(sprintf(
                'Argument #1 $route must be of type Route,'
                . ' and its property Route::$sourcePath must be a valid, accessible path, but "%s" given.',
                $this->path,
            ));
        }
    }
}
