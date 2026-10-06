<?php

namespace ControleOnline\Service;

use App\Service\EmailService;
use ControleOnline\Entity\Email;
use ControleOnline\Entity\PasswordRecovery;
use ControleOnline\Entity\RecoveryAccess;
use ControleOnline\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PasswordRecoveryService
{
    public function __construct(
        private EntityManagerInterface $manager,
        private EmailService $emailService,
        private UserService $userService,
        private PublicAppUrlResolver $publicAppUrlResolver,
        private ?ValidatorInterface $validator = null,
        private ?PasswordPolicyService $passwordPolicy = null
    ) {}

    public function requestRecoveryFromContent(?string $content): void
    {
        $payload = $this->createPasswordRecoveryPayload(
            $this->decodePayload($content)
        );
        $this->validatePayload($payload);

        $this->requestRecovery($payload);
    }

    public const TEMPORARY_PASSWORD_TTL_MINUTES = 15;

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

        // Temporary password flow (app-community#68):
        // user logs in with the temporary password and MUST change it within 15 minutes.
        $temporaryPassword = $this->generateTemporaryPassword();
        $deadline = new \DateTimeImmutable(
            sprintf('+%d minutes', self::TEMPORARY_PASSWORD_TTL_MINUTES)
        );

        $this->userService->applyTemporaryPassword($user, $temporaryPassword, $deadline);

        // Invalidate any previous recovery-link tokens so only the temporary password is valid.
        $user
            ->setOauthHash(null)
            ->setLostPassword(null);

        $this->manager->persist($user);
        $this->manager->flush();

        $this->emailService->sendMessage(
            $recipient,
            'Senha temporaria - recuperacao de acesso',
            $this->buildTemporaryPasswordEmail($user, $temporaryPassword, $deadline)
        );
    }

    public function completeRecoveryFromContent(?string $content): void
    {
        $payload = $this->createRecoveryAccessPayload(
            $this->decodePayload($content)
        );
        $this->validatePayload($payload);

        $this->completeRecovery($payload);
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

        // Link-based recovery still supported; clears any temporary-password flags.
        $this->userService->changePasswordForRecovery($user, (string) $payload->password);
    }

    private function findUserForRecovery(PasswordRecovery $payload): ?User
    {
        $login = $this->normalizeEmail($payload->username ?? '');
        $email = $this->normalizeEmail($payload->email ?? '');

        if ($login === '' && $email === '') {
            return null;
        }

        $userRepository = $this->manager->getRepository(User::class);

        if ($login !== '') {
            $user = $userRepository->findOneBy([
                'username' => $login,
            ]);

            if ($user instanceof User) {
                if ($email === '' || $this->matchesUserEmail($user, $email)) {
                    return $user;
                }
            }
        }

        if ($email === '') {
            return null;
        }

        $user = $userRepository->findOneBy([
            'username' => $email,
        ]);

        if ($user instanceof User) {
            return $user;
        }

        $emailEntity = $this->manager->getRepository(Email::class)->findOneBy([
            'email' => $email,
        ]);

        if (!$emailEntity instanceof Email || !$emailEntity->getPeople()) {
            return null;
        }

        return $this->resolveUserFromPeople(
            $emailEntity->getPeople(),
            $login,
            $email
        );
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

        foreach ($user->getPeople()->getEmail() as $peopleEmail) {
            if ($this->normalizeEmail($peopleEmail->getEmail()) === $email) {
                return true;
            }
        }

        return false;
    }

    private function normalizeEmail(string $value): string
    {
        return strtolower(trim($value));
    }

    private function createPasswordRecoveryPayload(array $payload): PasswordRecovery
    {
        $recovery = new PasswordRecovery();
        $recovery->username = $this->extractString(
            $payload,
            ['username', 'login', 'user', 'email']
        );
        $recovery->email = $this->extractString(
            $payload,
            ['email', 'username', 'login']
        );

        return $recovery;
    }

    private function createRecoveryAccessPayload(array $payload): RecoveryAccess
    {
        $recovery = new RecoveryAccess();
        $recovery->hash = $this->extractString($payload, ['hash']);
        $recovery->lost = $this->extractString($payload, ['lost']);
        $recovery->password = $this->extractString($payload, ['password']);
        $recovery->confirm = $this->extractString(
            $payload,
            ['confirm', 'passwordConfirmation', 'confirmPassword']
        );

        return $recovery;
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

        $message = (string) $violations[0]->getMessage();
        if ($this->passwordPolicy instanceof PasswordPolicyService) {
            $message = $this->passwordPolicy->mapErrorMessage($message);
        }
        throw new Exception($message);
    }

    private function resolveUserFromPeople(
        mixed $people,
        string $login = '',
        string $email = ''
    ): ?User {
        $users = $this->manager->getRepository(User::class)->findBy([
            'people' => $people,
        ]);

        foreach ($users as $user) {
            if (
                $login !== '' &&
                $this->normalizeEmail($user->getUsername()) === $login
            ) {
                return $user;
            }
        }

        foreach ($users as $user) {
            if ($email !== '' && $this->matchesUserEmail($user, $email)) {
                return $user;
            }
        }

        return $users[0] ?? null;
    }

    private function generateTemporaryPassword(int $length = 10): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        $max = strlen($alphabet) - 1;
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }
        return $password;
    }

    private function buildTemporaryPasswordEmail(
        User $user,
        string $temporaryPassword,
        \DateTimeImmutable $deadline
    ): string {
        $name = htmlspecialchars(
            $user->getPeople()->getFullName() ?: $user->getUsername(),
            ENT_QUOTES,
            'UTF-8'
        );
        $safePassword = htmlspecialchars($temporaryPassword, ENT_QUOTES, 'UTF-8');
        $minutes = self::TEMPORARY_PASSWORD_TTL_MINUTES;
        $deadlineLabel = htmlspecialchars(
            $deadline->format('d/m/Y H:i'),
            ENT_QUOTES,
            'UTF-8'
        );
        $loginUrl = htmlspecialchars($this->resolvePublicAppUrl() . '/login', ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<div style="font-family: Arial, sans-serif; color: #0f172a; line-height: 1.6;">
                <h2 style="margin-bottom: 12px;">Senha temporaria</h2>
                <p>Ola, %s.</p>
                <p>Recebemos uma solicitacao de recuperacao de senha.</p>
                <p>Sua <strong>senha temporaria</strong> e:</p>
                <p style="font-size: 20px; letter-spacing: 2px; font-weight: bold;">%s</p>
                <p>Faca login com esta senha e <strong>troque-a obrigatoriamente em ate %d minutos</strong> (ate %s).</p>
                <p>Apos o login voce sera direcionado para a tela de troca de senha. Depois da troca, a senha temporaria deixa de valer.</p>
                <p><a href="%s">Acessar o login</a></p>
                <p>Se voce nao solicitou a recuperacao, ignore este e-mail e altere sua senha por precaução se tiver acesso.</p>
            </div>',
            $name,
            $safePassword,
            $minutes,
            $deadlineLabel,
            $loginUrl
        );
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

    /**
     * Resolve frontend base URL for email links.
     * Prefer tenant request domain (app-domain / Origin / Referer) over global ENV
     * so multi-tenant apps receive correct reset links.
     */
    private function resolvePublicAppUrl(): string
    {
        return $this->publicAppUrlResolver->resolve();
    }
}
