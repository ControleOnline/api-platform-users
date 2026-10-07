<?php

namespace App\Service;

/** Signature-only fixture for the mail transport owned by api-community. */
class EmailService
{
    public function sendMessage(string $recipient, string $subject, string $bodyHtml): void
    {
        throw new \LogicException('Tests must mock the consumer mail transport.');
    }
}
