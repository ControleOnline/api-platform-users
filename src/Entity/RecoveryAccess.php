<?php

namespace ControleOnline\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ControleOnline\Controller\CompletePasswordRecoveryAction;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/recovery_accesses',
            controller: CompletePasswordRecoveryAction::class,
            security: 'is_granted(\'PUBLIC_ACCESS\')',
            read: false,
            output: false
        ),
    ],
    formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']]
)]
final class RecoveryAccess
{
    /**
     * @Assert\NotBlank
     */
    public $hash;
    /**
     * @Assert\NotBlank
     */
    public $lost;
    /**
     * @Assert\NotBlank
     * @Assert\Length(
     *    min        = 6,
     *    minMessage = "Your password name must be at least {{ limit }} characters long",
     * )
     * @Assert\NotCompromisedPassword
     */
    public $password;
    /**
     * @Assert\NotBlank
     * @Assert\Expression(
     *     "this.password === this.confirm",
     *     message="Password and Confirm Password must be identical"
     * )
     */
    public $confirm;
}
