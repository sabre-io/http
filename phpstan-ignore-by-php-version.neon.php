<?php

declare(strict_types=1);

$includes = [];
if (PHP_VERSION_ID >= 80000) {
    $includes[] = __DIR__.'/phpstan-ignore-php8.neon';
} else {
    $includes[] = __DIR__.'/phpstan-ignore-php74.neon';
}

$config = [];
$config['includes'] = $includes;

// overrides config.platform.php in composer.json
$config['parameters']['phpVersion'] = PHP_VERSION_ID;

return $config;
