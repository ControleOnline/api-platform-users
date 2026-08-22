<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ControleOnline\Controller\CompletePasswordRecoveryAction;
use ControleOnline\Service\PasswordPolicyService;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/recovery_accesses',
            controller: CompletePasswordRecoveryAction::class,
            security: 'is_granted(\'PUBLIC_ACCESS\')',
            deserialize: false,
            read: false,
            output: false
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']]
)]
final class RecoveryAccess
{
    #[Assert\NotBlank]
    public $hash;

    #[Assert\NotBlank]
    public $lost;

    #[Assert\NotBlank(message: PasswordPolicyService::MSG_REQUIRED)]
    #[Assert\Length(
        min: PasswordPolicyService::MIN_LENGTH,
        minMessage: 'A senha precisa ter pelo menos {{ limit }} caracteres.',
    )]
    #[Assert\NotCompromisedPassword(message: PasswordPolicyService::MSG_COMPROMISED)]
    public $password;

    #[Assert\NotBlank]
    #[Assert\Expression(
        'this.password === this.confirm',
        message: PasswordPolicyService::MSG_CONFIRM_MISMATCH,
    )]
    public $confirm;
}
