<?php

namespace ControleOnline\Service;

use App\Service\EmailService;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\PasswordRecovery;
use ControleOnline\Entity\RecoveryAccess;
use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;

class PasswordRecoveryService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private EmailService $emailService,
        private UserService $userService,
        private DomainService $domainService
    ) {}

    public function requestRecovery(PasswordRecovery $payload): void
    {
        $user = $this->findUserForRecovery($payload);

        if (!$user instanceof User) {
            return;
        }

        $recipient = $this->resolveRecipientEmail($user, $payload);
        if ($recipient === null) {
            return;
        }

        $hash = bin2hex(random_bytes(20));
        $lost = bin2hex(random_bytes(24));

        $user
            ->setOauthHash($hash)
            ->setLostPassword($lost);

        $this->manager->persist($user);
        $this->manager->flush();

        $this->emailService->sendMessage(
            $recipient,
            'Recuperacao de senha',
            $this->buildRecoveryEmail($user, $hash, $lost)
        );
    }

    public function completeRecovery(RecoveryAccess $payload): void
    {
        $hash = trim((string) $payload->hash);
        $lost = trim((string) $payload->lost);

        $user = $this->manager->getRepository(User::class)->findOneBy([
            'oauthHash' => $hash,
            'lostPassword' => $lost,
        ]);

        if (!$user instanceof User) {
            throw new Exception('Solicitacao de recuperacao invalida ou expirada.');
        }

        $this->userService->changePassword($user, (string) $payload->password);

        $user
            ->setOauthHash(null)
            ->setLostPassword(null);

        $this->manager->persist($user);
        $this->manager->flush();
    }

    private function findUserForRecovery(PasswordRecovery $payload): ?User
    {
        $login = $this->normalizeEmail($payload->username ?? '');
        $email = $this->normalizeEmail($payload->email ?? '');

        if ($login === '' || $email === '') {
            return null;
        }

        $user = $this->manager->getRepository(User::class)->findOneBy([
            'username' => $login,
        ]);

        if ($user instanceof User) {
            return $this->matchesUserEmail($user, $email) ? $user : null;
        }

        $emailEntity = $this->manager->getRepository(Email::class)->findOneBy([
            'email' => $email,
        ]);

        if (!$emailEntity instanceof Email || !$emailEntity->getPeople()) {
            return null;
        }

        $user = $this->manager->getRepository(User::class)->findOneBy([
            'people' => $emailEntity->getPeople(),
        ]);

        if (!$user instanceof User) {
            return null;
        }

        if ($this->normalizeEmail($user->getUsername()) !== $login) {
            return null;
        }

        return $user;
    }

    private function resolveRecipientEmail(
        User $user,
        PasswordRecovery $payload
    ): ?string {
        $requestedEmail = $this->normalizeEmail($payload->email ?? '');

        if ($requestedEmail !== '' && $this->matchesUserEmail($user, $requestedEmail)) {
            return $requestedEmail;
        }

        $primaryEmail = $user->getPeople()->getOneEmail()?->getEmail();
        $primaryEmail = $this->normalizeEmail($primaryEmail ?? '');

        if ($primaryEmail !== '') {
            return $primaryEmail;
        }

        $username = $this->normalizeEmail($user->getUsername());
        return $username !== '' ? $username : null;
    }

    private function matchesUserEmail(User $user, string $email): bool
    {
        if ($email === '') {
            return false;
        }

        if ($this->normalizeEmail($user->getUsername()) === $email) {
            return true;
        }

        $peopleEmail = $user->getPeople()->getOneEmail()?->getEmail();
        return $this->normalizeEmail($peopleEmail ?? '') === $email;
    }

    private function normalizeEmail(string $value): string
    {
        return strtolower(trim($value));
    }

    private function buildRecoveryEmail(User $user, string $hash, string $lost): string
    {
        $name = htmlspecialchars(
            $user->getPeople()->getFullName() ?: $user->getUsername(),
            ENT_QUOTES,
            'UTF-8'
        );

        $link = htmlspecialchars(
            $this->buildRecoveryUrl($hash, $lost),
            ENT_QUOTES,
            'UTF-8'
        );

        return sprintf(
            '<div style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.6;">
                <h2 style="margin-bottom: 12px;">Recuperacao de senha</h2>
                <p>Ola, %s.</p>
                <p>Recebemos uma solicitacao para redefinir a sua senha.</p>
                <p>Use o link temporario abaixo para cadastrar uma nova senha:</p>
                <p><a href="%s">%s</a></p>
                <p>Se voce nao solicitou a recuperacao, basta ignorar este e-mail.</p>
            </div>',
            $name,
            $link,
            $link
        );
    }

    private function buildRecoveryUrl(string $hash, string $lost): string
    {
        $baseUrl = $this->resolvePublicAppUrl();

        return sprintf(
            '%s/reset-password?hash=%s&lost=%s',
            $baseUrl,
            rawurlencode($hash),
            rawurlencode($lost)
        );
    }

    private function resolvePublicAppUrl(): string
    {
        $domain = $_ENV['PUBLIC_APP_DOMAIN']
            ?? $_ENV['MANAGER_APP']
            ?? $_ENV['APP_DOMAIN']
            ?? $_ENV['ADMIN_APP_DOMAIN']
            ?? $_SERVER['PUBLIC_APP_DOMAIN']
            ?? $_SERVER['MANAGER_APP']
            ?? $_SERVER['APP_DOMAIN']
            ?? $_SERVER['ADMIN_APP_DOMAIN']
            ?? getenv('PUBLIC_APP_DOMAIN')
            ?? getenv('MANAGER_APP')
            ?? getenv('APP_DOMAIN')
            ?? getenv('ADMIN_APP_DOMAIN')
            ?? '';

        if ($domain === '') {
            try {
                $domain = (string) $this->domainService->getDomain();
            } catch (\Throwable) {
                $domain = '';
            }
        }

        $domain = trim((string) $domain);
        if ($domain === '') {
            $domain = 'admin.controleonline.com';
        }

        if (!preg_match('#^https?://#i', $domain)) {
            $domain = 'https://' . ltrim($domain, '/');
        }

        return rtrim($domain, '/');
    }
}
