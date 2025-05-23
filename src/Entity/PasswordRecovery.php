<?php

namespace ControleOnline\Entity;

use Symfony\Component\Serializer\Attribute\Groups; 
use ControleOnline\Listener\LogListener;

use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiFilter;
use Symfony\Component\Validator\Constraints as Assert;
/**
 */
#[ApiResource(operations: [new Post(status: 202)], formats: ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']], security: 'is_granted(\'PUBLIC_ACCESS\')', messenger: true)]
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
