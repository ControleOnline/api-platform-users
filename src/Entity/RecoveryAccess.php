<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ControleOnline\Controller\RecoveryAccessAction;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/recovery_accesses',
            controller: RecoveryAccessAction::class,
            read: false,
            output: false,
            status: 200,
            security: 'is_granted(\'PUBLIC_ACCESS\')'
        )
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']]
)]
final class RecoveryAccess
{
    public $token;

    public $hash;

    public $lost;

    #[Assert\NotBlank]
    #[Assert\Length(
        min: 6,
        minMessage: 'Your password name must be at least {{ limit }} characters long'
    )]
    #[Assert\NotCompromisedPassword]
    public $password;

    #[Assert\NotBlank]
    #[Assert\Expression(
        "this.password === this.confirm",
        message: 'Password and Confirm Password must be identical'
    )]
    public $confirm;
}
