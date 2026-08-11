<?php

namespace ControleOnline\Service;

use App\Service\EmailService;
use ControleOnline\Entity\AccountVerificationAccess;
use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class AccountVerificationService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private EmailService $emailService,
        private PublicAppUrlResolver $publicAppUrlResolver,
        private ?ValidatorInterface $validator = null
    ) {}

    public function sendVerification(User $user, ?string $recipient = null): void
    {
        $recipientEmail = $this->resolveRecipientEmail($user, $recipient);
        if ($recipientEmail === null) {
            throw new Exception('Nao foi possivel identificar um e-mail valido para confirmacao.');
        }

        $hash = bin2hex(random_bytes(20));
        $token = bin2hex(random_bytes(24));

        $user
            ->setOauthHash($hash)
            ->setLostPassword($token);

        $this->manager->persist($user);
        $this->manager->flush();

        $this->emailService->sendMessage(
            $recipientEmail,
            'Confirme seu cadastro',
            $this->buildVerificationEmail($user, $hash, $token)
        );
    }

    public function completeVerificationFromContent(?string $content): void
    {
        $payload = $this->createVerificationPayload(
            $this->decodePayload($content)
        );
        $this->validatePayload($payload);

        $this->completeVerification($payload);
    }

    public function completeVerification(AccountVerificationAccess $payload): void
    {
        $hash = trim((string) $payload->hash);
        $token = trim((string) $payload->token);

        $user = $this->manager->getRepository(User::class)->findOneBy([
            'oauthHash' => $hash,
            'lostPassword' => $token,
        ]);

        if (!$user instanceof User) {
            throw new Exception('Link de confirmação inválido, já utilizado ou substituído por um novo envio.');
        }

        $people = $user->getPeople();
        $people->setEnabled(true);

        $user
            ->setOauthHash(null)
            ->setLostPassword(null);

        $this->manager->persist($people);
        $this->manager->persist($user);
        $this->manager->flush();
    }

    private function resolveRecipientEmail(User $user, ?string $recipient): ?string
    {
        $recipient = $this->normalizeEmail($recipient ?? '');
        if ($recipient !== '') {
            return $recipient;
        }

        $primaryEmail = $this->normalizeEmail(
            $user->getPeople()->getOneEmail()?->getEmail() ?? ''
        );
        if ($primaryEmail !== '') {
            return $primaryEmail;
        }

        $username = $this->normalizeEmail($user->getUsername());
        return $username !== '' ? $username : null;
    }

    private function normalizeEmail(string $value): string
    {
        return strtolower(trim($value));
    }

    private function createVerificationPayload(array $payload): AccountVerificationAccess
    {
        $access = new AccountVerificationAccess();
        $access->hash = $this->extractString($payload, ['hash']);
        $access->token = $this->extractString($payload, ['token', 'lost']);

        return $access;
    }

    private function decodePayload(?string $content): array
    {
        $content = trim((string) $content);
        if ($content === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function extractString(array $payload, array $keys): string
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $payload)) {
                continue;
            }

            $value = $payload[$key];
            if (is_scalar($value)) {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function validatePayload(object $payload): void
    {
        if (!$this->validator instanceof ValidatorInterface) {
            return;
        }

        $violations = $this->validator->validate($payload);
        if (count($violations) === 0) {
            return;
        }

        throw new Exception((string) $violations[0]->getMessage());
    }

    private function buildVerificationEmail(User $user, string $hash, string $token): string
    {
        $name = htmlspecialchars(
            $user->getPeople()->getFullName() ?: $user->getUsername(),
            ENT_QUOTES,
            'UTF-8'
        );

        $link = htmlspecialchars(
            $this->buildVerificationUrl($hash, $token),
            ENT_QUOTES,
            'UTF-8'
        );

        return sprintf(
            '<div style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.6;">
                <h2 style="margin-bottom: 12px;">Confirme seu cadastro</h2>
                <p>Ola, %s.</p>
                <p>Clique no link abaixo para confirmar sua conta:</p>
                <p><a href="%s">%s</a></p>
                <p>Se voce nao criou esta conta, ignore este e-mail.</p>
            </div>',
            $name,
            $link,
            $link
        );
    }

    private function buildVerificationUrl(string $hash, string $token): string
    {
        $baseUrl = $this->resolvePublicAppUrl();

        return sprintf(
            '%s/confirm-account?hash=%s&token=%s',
            $baseUrl,
            rawurlencode($hash),
            rawurlencode($token)
        );
    }

    /**
     * Resolve frontend base URL for email links.
     * Prefer tenant request domain (app-domain / Origin / Referer) over global ENV
     * so multi-tenant apps receive correct confirmation links.
     */
    private function resolvePublicAppUrl(): string
    {
        return $this->publicAppUrlResolver->resolve();
    }
}
