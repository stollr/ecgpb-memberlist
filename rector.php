<?php

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withImportNames(
        importNames: true,
        importShortClasses: false,
    )
    ->withComposerBased(
        twig: true,
        doctrine: true,
        phpunit: true,
        symfony: true,
    )
    ->withAttributesSets(
        symfony: true,
        doctrine: true,
        gedmo: true,
        phpunit: true,
    )
;
