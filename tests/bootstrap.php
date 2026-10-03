<?php

declare(strict_types=1);

$autoloaders = [
    dirname(__DIR__, 2) . '/api-community/vendor/autoload.php',
    dirname(__DIR__) . '/vendor/autoload.php',
];

foreach ($autoloaders as $autoloader) {
    if (is_file($autoloader)) {
        require $autoloader;
        return;
    }
}

throw new RuntimeException('Composer autoloader not found. Install dependencies before running tests.');
