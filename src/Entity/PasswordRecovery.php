<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ControleOnline\Controller\RequestPasswordRecoveryAction;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/password_recoveries',
            controller: RequestPasswordRecoveryAction::class,
            security: 'is_granted(\'PUBLIC_ACCESS\')',
            deserialize: false,
            read: false,
            output: false,
            status: 202
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']]
)]
final class PasswordRecovery
{
    /**
     * @Assert\NotBlank
     */
    public $username;

    /**
     * @Assert\NotBlank
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email.",
     *     mode    = "html5",
     * )
     */
    public $email;
}
