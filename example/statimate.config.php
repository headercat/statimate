<?php

declare(strict_types=1);

use Headercat\Statimate\Statimate;

return new Statimate()
    ->withRouteDir(__DIR__ . '/routes')
    ->withBuildDir(__DIR__ . '/build');
