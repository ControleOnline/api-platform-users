<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Email;
use ControleOnline\Entity\Language;
use ControleOnline\Entity\People;
use ControleOnline\Entity\User;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait AccountRegistrationTrait
{
    public function createAccountSessionFromContent(?string $content): array
    {
        $payload = $this->decodePayload($content);
        foreach (['name', 'email', 'password'] as $field) {
            if (!isset($payload[$field]) || !is_string($payload[$field]) || trim($payload[$field]) === '') {
                throw new BadRequestHttpException('name, email and password are required');
            }
        }
        if (isset($payload['confirmPassword']) && $payload['confirmPassword'] !== $payload['password']) {
            throw new BadRequestHttpException('password confirmation does not match');
        }
        $emailAddress = strtolower(trim($payload['email']));
        if (!filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestHttpException('email is invalid');
        }
        // Public registration must never claim an existing identity by an unverified email.
        if ($this->manager->getRepository(User::class)->findOneBy(['username' => $emailAddress]) ||
            $this->manager->getRepository(Email::class)->findOneBy(['email' => $emailAddress])) {
            throw new BadRequestHttpException('Account already exists');
        }
        $this->passwordPolicy?->assertValid($payload['password']);
        $timezone = $this->resolveDefaultTimezone();
        $language = $this->manager->getRepository(Language::class)->findOneBy(['language' => 'pt-BR']);
        if (!$language instanceof Language) {
            throw new BadRequestHttpException('language is required');
        }
        $parts = preg_split('/\s+/', trim($payload['name']), 2);
        $people = (new People())->setAlias($parts[0])->setName($parts[1] ?? '')->setLanguage($language);
        $email = (new Email())->setEmail($emailAddress)->setPeople($people);
        $people->getEmail()->add($email);
        $user = $this->buildNewUser($people, $emailAddress, $payload['password'], $timezone);
        // Validate/build the response before the first persistence operation.
        $session = $this->getUserSession($user);
        $this->manager->persist($people);
        $this->manager->persist($email);
        $this->manager->persist($user);
        $this->manager->flush();
        // Assigned IDs become available after flush.
        $session['id'] = $people->getId();
        $session['people'] = $people->getId();
        return $session;
    }
}
