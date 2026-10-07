<?php

// Standalone runs use the same installed contracts as an API consumer.
require_once dirname(__DIR__) . '/vendor/autoload.php';

// This transport is owned by api-community, not by this Composer package.
// Consumer runs retain their actual App service and never load this fixture.
if (!class_exists(\App\Service\EmailService::class)) {
    require_once __DIR__ . '/Support/ConsumerEmailTransport.php';
}
