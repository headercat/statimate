<?php

declare(strict_types=1);

namespace Headercat\Statimate\Compiler\Preset;

use Closure;
use Headercat\Statimate\Compiler\CompileTarget;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\Compilers\BladeCompiler as IlluminateBladeCompiler;
use Illuminate\View\ViewServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Blade template engine.
 * @see https://github.com/jenssegers/blade
 */
final class BladeCompiler implements ViewFactory
{
    private Container $container;
    private ViewFactory $factory;
    private IlluminateBladeCompiler $compiler;

    /**
     * Compile the provided compile-target.
     *
     * @param CompileTarget $target Target to compile.
     *
     * @return string
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function compileDocument(CompileTarget $target): string
    {
        $path = substr($target->path, strlen($target->config->projectDir) + 1, -10);
        return new self($target->config->projectDir)->render($path, $target->vars);
    }

    /**
     * Constructor.
     *
     * @param string         $viewPath Root view path which blade compiler should resolve.
     * @param Container|null $container
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(string $viewPath, Container|null $container = null)
    {
        $this->container = $container ?: new class extends Container {
            /** @var list<Closure> */
            protected array $terminatingCallbacks = [];

            /** @noinspection PhpUnused */
            public function terminating(Closure $callback): self
            {
                $this->terminatingCallbacks[] = $callback;
                return $this;
            }

            /** @noinspection PhpUnused */
            public function terminate(): void
            {
                array_walk($this->terminatingCallbacks, fn(Closure $cb) => $cb());
            }
        };

        $cachePath = sys_get_temp_dir() . '/Headercat/Statimate/Compiler/Preset/BladeCompiler';
        @mkdir($cachePath, 0777, true);
        $this->container->bindIf('files', fn() => new Filesystem());
        $this->container->bindIf('events', fn() => new Dispatcher());
        $this->container->bindIf('config', fn() => new Repository([
            'view.paths' => [$viewPath],
            'view.compiled' => $cachePath,
        ]));

        /** @noinspection PhpParamsInspection */
        Facade::setFacadeApplication($this->container); // @phpstan-ignore-line

        /** @noinspection PhpParamsInspection */
        new ViewServiceProvider($this->container)->register(); // @phpstan-ignore-line

        $this->factory = $this->container->get('view'); // @phpstan-ignore-line
        $this->compiler = $this->container->get('blade.compiler'); // @phpstan-ignore-line
    }

    /**
     * Render the blade template view.
     *
     * @param string               $view Template file path to render, relative to $viewPath.
     * @param array<string, mixed> $data Data to pass.
     *
     * @return string
     */
    public function render(string $view, array $data = []): string
    {
        return $this->make($view, $data)->render();
    }

    /**
     * Register a handler for custom directives.
     *
     * @param string                 $name    Directive name to register.
     * @param Closure(string):string $handler Handler to handle the directive.
     *
     * @return void
     */
    public function directive(string $name, Closure $handler): void
    {
        $this->compiler->directive($name, $handler);
    }

    /**
     * @param string               $path
     * @param array<string, mixed> $data
     * @param array<string, mixed> $mergeData
     *
     * @return View
     * @inheritdoc
     * @noinspection PhpUnused
     */
    public function file($path, $data = [], $mergeData = []): View
    {
        return $this->factory->file($path, $data, $mergeData);
    }

    /**
     * @param string               $view
     * @param array<string, mixed> $data
     * @param array<string, mixed> $mergeData
     *
     * @return View
     * @inheritdoc
     * @noinspection PhpUnused
     */
    public function make($view, $data = [], $mergeData = []): View
    {
        return $this->factory->make($view, $data, $mergeData);
    }

    /**
     * @inheritdoc
     * @noinspection PhpUnused
     */
    public function exists($view): bool
    {
        return $this->factory->exists($view);
    }

    /**
     * @inheritdoc
     * @noinspection PhpUnused
     * @phpstan-ignore-next-line
     */
    public function share($key, $value = null)
    {
        return $this->factory->share($key, $value);
    }

    /**
     * @inheritdoc
     * @noinspection PhpUnused
     * @phpstan-ignore-next-line
     */
    public function composer($views, $callback)
    {
        return $this->factory->composer($views, $callback);
    }

    /**
     * @inheritdoc
     * @noinspection PhpUnused
     * @phpstan-ignore-next-line
     */
    public function creator($views, $callback)
    {
        return $this->factory->creator($views, $callback);
    }

    /**
     * @inheritdoc
     * @noinspection PhpUnused
     * @phpstan-ignore-next-line
     */
    public function addNamespace($namespace, $hints): self
    {
        $this->factory->addNamespace($namespace, $hints);
        return $this;
    }

    /**
     * @inheritdoc
     * @noinspection PhpUnused
     * @phpstan-ignore-next-line
     */
    public function replaceNamespace($namespace, $hints): self
    {
        $this->factory->replaceNamespace($namespace, $hints);
        return $this;
    }

    /**
     * Magic method call.
     *
     * @param string      $name      Name of method called.
     * @param list<mixed> $arguments Arguments.
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments): mixed
    {
        return $this->factory->{$name}(...$arguments);
    }
}
