<?php

declare(strict_types=1);

namespace Headercat\Statimate\Config;

use Closure;
use Headercat\Statimate\Compiler\CompileTarget;
use Headercat\Statimate\Compiler\Preset\BladeCompiler;
use Headercat\Statimate\Compiler\Preset\MarkdownCompiler;
use Headercat\Statimate\Helper\Pagination;
use Headercat\Statimate\Plugin\PluginInterface;
use InvalidArgumentException;
use ReflectionFunction;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

final class StatimateConfig
{
    /**
     * @var string Directory path of the project root.
     */
    private(set) string $projectDir;

    /**
     * @var string Directory path of the route root.
     */
    private(set) string $routeDir;

    /**
     * @var string Directory path of the build output target.
     */
    private(set) string $buildDir;

    /**
     * @var array<string, Closure> Document compilers.
     */
    private(set) array $documentCompilers = [];

    /**
     * @var list<class-string<PluginInterface>> Registered plugin class names.
     */
    private(set) array $registeredPlugins = [];

    /**
     * Constructor.
     *
     * @param bool $withDefaultValues Initialize the configuration with default values.
     */
    public function __construct(bool $withDefaultValues = true)
    {
        Pagination::init($this);

        if (!$withDefaultValues) {
            return;
        }

        $this->setProjectDir($this->getAutoDetectedProjectDir());
        $this->setBuildDir($this->projectDir . '/build');
        try {
            $this->setRouteDir($this->projectDir . '/routes');
        } catch (Throwable) {
        }

        $this->addDocumentCompiler('.blade.php', BladeCompiler::compileDocument(...));
        $this->addDocumentCompiler('.md', MarkdownCompiler::compileDocument(...));
    }

    /**
     * Set the project root directory.
     *
     * @param string $projectDir Directory path of the project root.
     *
     * @return $this
     */
    public function setProjectDir(string $projectDir): self
    {
        $realDir = realpath($projectDir);
        if (!$realDir || !is_dir($realDir)) {
            throw new UnexpectedValueException(sprintf(
                'Argument #1 $projectDir must be a valid directory, but "%s" given.',
                $projectDir,
            ));
        }
        $this->projectDir = $realDir;
        return $this;
    }

    /**
     * Set route root directory.
     *
     * @param string $routeDir Directory path of the route root.
     *
     * @return $this
     */
    public function setRouteDir(string $routeDir): self
    {
        $realDir = realpath($routeDir);
        if (!$realDir || !is_dir($realDir)) {
            throw new UnexpectedValueException(sprintf(
                'Argument #1 $routeDir must be a valid directory, but "%s" given.',
                $routeDir,
            ));
        }
        $this->routeDir = $realDir;
        return $this;
    }

    /**
     * Set the build output target directory.
     *
     * @param string $buildDir Directory path of the build output target.
     *
     * @return $this
     */
    public function setBuildDir(string $buildDir): self
    {
        if (!file_exists($buildDir)) {
            mkdir($buildDir, 0777, true);
        }
        $realDir = realpath($buildDir);
        if (!$realDir || !is_dir($realDir)) {
            throw new UnexpectedValueException(sprintf(
                'Argument #1 $buildDir must be a valid directory, but "%s" given.',
                $buildDir
            ));
        }
        $this->buildDir = $realDir;
        return $this;
    }

    /**
     * Add document compiler.
     *
     * @param string                        $extension Extension name with a leading dot.
     * @param Closure(CompileTarget):string $compiler  Compiler function.
     *
     * @return $this
     */
    public function addDocumentCompiler(string $extension, Closure $compiler): self
    {
        try {
            $refFunction = new ReflectionFunction($compiler);
            $refParams = $refFunction->getParameters();
            foreach ($refParams as $i => $refParam) {
                if ($i === 0) {
                    if ($type = $refParam->getType()?->__toString()) {
                        if (!array_any(
                            explode('|', str_replace('?', '', $type)),
                            fn(string $v) => $v === CompileTarget::class
                        )) {
                            throw new InvalidArgumentException();
                        }
                    }
                    continue;
                }
                if (!$refParam->isOptional()) {
                    throw new InvalidArgumentException();
                }
            }
            $type = $refFunction->getReturnType();
            if ($type && $type->__toString() !== 'string') {
                throw new InvalidArgumentException();
            }
        } catch (Throwable) {
            throw new InvalidArgumentException(sprintf(
                'Argument #2 $compiler must be compatible of type Closure(CompileTarget):string, but %s given.',
                get_debug_type($compiler),
            ));
        }

        $extension = '.' . ltrim(strtolower($extension), '.');
        $this->documentCompilers[$extension] = $compiler;
        return $this;
    }

    /**
     * Add plugin.
     *
     * @param class-string<PluginInterface> $pluginClass Class name of the plugin.
     *
     * @return self
     */
    public function addPlugin(string $pluginClass): self
    {
        if (in_array($pluginClass, $this->registeredPlugins)) {
            throw new UnexpectedValueException(sprintf(
                'Argument #1 $pluginClass must be registered once, but given "%s" already registered.',
                $pluginClass,
            ));
        }
        new $pluginClass()->register($this);
        return $this;
    }

    /**
     * Auto-detect the project root directory based on debug backtrace.
     *
     * @return string
     */
    private function getAutoDetectedProjectDir(): string
    {
        $notFoundMessage = 'Cannot determine the project root directory.'
            . ' Set withDefaultValues: false, and use setProjectDir() instead.';

        $backtrace = array_reverse(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS));
        foreach ($backtrace as $trace) {
            if (
                isset($trace['class']) && $trace['class'] === self::class
                && $trace['function'] === '__construct'
                && isset($trace['file'])
            ) {
                return dirname($trace['file']);
            }
        }
        throw new RuntimeException($notFoundMessage);
    }
}
