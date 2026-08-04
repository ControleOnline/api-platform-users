<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ControleOnline\Controller\CompleteAccountVerificationAction;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/account_verifications',
            controller: CompleteAccountVerificationAction::class,
            security: 'is_granted(\'PUBLIC_ACCESS\')',
            deserialize: false,
            read: false,
            output: false
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']]
)]
final class AccountVerificationAccess
{
    #[Assert\NotBlank]
    public $hash;

    #[Assert\NotBlank]
    public $token;
}
