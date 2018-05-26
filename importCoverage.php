<?php
if (version_compare('7.1.0', PHP_VERSION, '>')) {
    fwrite(
        STDERR,
        sprintf(
            'This test selector is supported on PHP 7.1 and PHP 7.2.' . PHP_EOL .
            'You are using PHP %s (%s).' . PHP_EOL,
            PHP_VERSION,
            PHP_BINARY
        )
    );

    die(1);
}

use AronSzigetvari\TestSelector\Command\ImportCoverage;
include __DIR__ . '/vendor/autoload.php';

ImportCoverage::main();
