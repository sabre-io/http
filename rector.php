<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/examples',
        __DIR__.'/lib',
        __DIR__.'/tests',
    ])
    ->withPhpSets(false, false, false, false, true)
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
