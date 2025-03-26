<?php
/**
 * You can open the web server with: php -S localhost:8080
 * Then open http://localhost:8080/ with your browser.
 *
 * Use this file for production is highly discouraged.
 * Build::serve() method always rebuild the entire app, and serve via file_get_content(), echo() chain.
 * There are many issues such as $_GET security problem, terrible performance, and we don't have any plan to fix them.
 */

declare(strict_types=1);

use Headercat\Statimate\Build;

require __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/statimate.config.php';
new Build($config)->serve();
