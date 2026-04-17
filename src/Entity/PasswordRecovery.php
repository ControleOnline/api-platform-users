<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ControleOnline\Controller\PasswordRecoveryAction;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/password_recoveries',
            controller: PasswordRecoveryAction::class,
            read: false,
            output: false,
            status: 200,
            security: 'is_granted(\'PUBLIC_ACCESS\')'
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']]
)]
final class PasswordRecovery
{
    #[Assert\NotBlank]
    public $username;

    #[Assert\NotBlank]
    #[Assert\Email(
        message: "The email '{{ value }}' is not a valid email.",
        mode: 'html5'
    )]
    public $email;
}
