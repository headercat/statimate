<?php

declare(strict_types=1);

use Headercat\Statimate\Config\StatimateConfig;

return new StatimateConfig()
    ->setRouteDir(__DIR__ . '/routes')
    ->setBuildDir(__DIR__ . '/build');
