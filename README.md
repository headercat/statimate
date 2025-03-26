# Statimate

Static site generator for minimalist. No complicated lock-in API, no complicated setup, no messy plugins.

## Getting Started

### Why statimate?

Statimate is heavily inspired by 11ty, which aims to be a lightweight SSG, but using 11ty in production requires using
some of its complex APIs. In particular, hacky approaches to choosing the right layout, or excessive configurations to
extend the JS-eco template engine, seem like unnecessary complexity, at least to PHP users.

Statimate remove away unnecessary APIs and keeps it small enough to build simple company sites, blogs, and more.

### Installation

Statimate supports PHP 8.4 or later.

This will create a `/statimate.config.php` which contains some configuration, `/statimate` a shell script that operates a
build, and an example `/routes/index.blade.php` file.

```shell
composer require headercat/statimate && ./vendor/bin/init
```

### Summary

* Example [here](/example).
* `blade.php`, `html`, `md` for templating. Just simple copy otherwise.
* `/[foo]/?.php` for placeholders. `?.php` must return `array<string|int|float>`. Use it from template file via `$_GET['foo']`.
* `/@.blade.php` for layout. Nested layout supported. Use `{!! $_GET['content'] !!}` to print the child content.

## Routing

Statimate has two kinds of routes: documentation routes and static routes.

* Documentation routes are routes that are built through the template engine and layout handler.
By default, this includes files with the `blade.php`, `html` and `md` extensions. You can change the extension list via
`withDocumentExtensions()` method.

* Static routes are routes that are copied directly to the build directory without any modification. These are all routes
that don't fall under the documentation routes.

### Index

Documentation routes are built into `{filename}/index.html` to create a clean URL address. For example, if you have a
file named `/routes/foo/bar.blade.php`, it will be built as `foo/bar/index.html`.

### Placeholder

Placeholder can be used when you want to create multiple URLs using the same file.

```php
<?php # /routes/[page]/?.php
return [ 1, 2, 3 ];
```
```html
<!-- 
/routes/[page]/index.blade.php -> will be built as /routes/1/index.html, /routes/2/index.html, /routes/3/index.html
-->
<h1>Page: {{ $_GET['page'] }}</h1>
```

First, you need to create a folder like `/routes/[page]`, and create a file called `/routes/[page]/?.php`.
`?.php` is the reserved filename, which is responsible for returning the value `array<string|int|float>` that will go 
into the placeholder. Placeholders can be nested, and if they are, they will expand to the full number of cases in each 
case.

Each placeholder-ed documentation route file can get the value of its placeholder. How they are assigned depends on the
renderer you are using. If you are using the default blade engine, you can get it via `$_GET['placeholderName']`.

Note that static files do not follow placeholders. The route `/routes/[foo]/style.css` is built as `[foo]/style.css`, so
it should be referenced as `<link href="/[foo]/style.css" />"` in HTML, etc.

### Layout

Layouts are special file that wrap sub documentation routes.

```html
<!-- /routes/@.blade.php -->
<h1>Logo</h1>
<main>{!! $_GET['content'] !!}</main>
```
```html
<!--
/routes/index.blade.php -> will be compiled as...
<h1>Logo</h1>
<main><b>Hello World!</b></main>
-->
<b>Hello World!</b>
```

Files with `@.*` filenames are specially treated as layout files, and are not collected to the documentation routes. 
Layouts can be nested, and if so, they are applied in order, starting with the deepest layout.

The child content is passed in the same way as the placeholder variable is passed, with the name `content`. If you are 
using the default blade engine, use `{!! $_GET['content'] !!}` to print the child content.

## Misc

### Configuration API

Every method below will return a new instance with the provided new configuration value.

* `new Statimate([string $rootDir])`: Create a new statimate configuration instance.
* `withRouteDir(string $routeDir)`: Set the route directory. Default value is `{$rootDir}/routes`.
* `withBuildDir(string $buildDir)`: Set the build output directory.
* `withDocumentExtensions(list<string> $documentExtensions)`:
Set the file extensions that should be treated as documentation route. Default value is `[ 'blade.php', 'html', 'md' ]`
* `withExcludedExtensions(list<string> $excludedExtensions, bool $force = false)`:
Set the file extensions that should be excluded. If you leave `$force` as `false`, it automatically adds `php` to the 
value. Default value is `[ 'php' ]`.
* `withAddedRenderer(Closure(string,array<string,string>):(string|false) $renderer)`:
Add a new renderer. For more information, go to [Renderer::renderBlade()](/src/Template/Renderer.php#L55).

### How to preview without to build?

There's `serve()` method in `Build` class. Use it with `php -S localhost:8080`. 
For more information, look [example](/example/index.php).

### Why PHP?

Laravel Blade is the most effective template engine we've ever seen. We've considered to implement Blade in other 
languages, but to make it worth, We'd have to create IDE extensions to support it. It's not a project that can kept small.

### Why no `composer create-project`?

`create-project` requires a separator repository to be a template. Creating another repository for non-critical feature 
like project creation would be a hindrance to keeping Statimate small.

### Why `$_GET`?

We know that `$_GET` is not a very descriptive word for placeholders, but it's too ugly to see IntelliJ's blade plugin 
draws red-line to variables from outside as undefined variables, so we just picked a global variable that satisfies the
`array<string, string>` type and has a reasonably similar semantics.

## License

Statimate is open-sourced software licensed under the [MIT license](LICENSE.md).
