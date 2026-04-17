<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\User;
use ControleOnline\Repository\UserRepository;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Twig\Environment;

class PasswordRecoveryService
{
    private const RECOVERY_TTL = 'PT15M';
    private const RECOVERY_TOKEN_PATTERN = '/^(?<issued_at>\d{14})-(?<random>[a-f0-9]{40})$/i';

    private TransportInterface $mailer;

    public function __construct(
        private EntityManagerInterface $manager,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private RequestStack $requestStack,
        private Environment $twig,
        #[Autowire('%app_name%')]
        private string $appName
    ) {
        $dsn = $_ENV['MAILER_URL']
            ?? $_ENV['MAILER_DSN']
            ?? $_SERVER['MAILER_URL']
            ?? $_SERVER['MAILER_DSN']
            ?? getenv('MAILER_URL')
            ?? getenv('MAILER_DSN')
            ?? 'null://null';

        $this->mailer = Transport::fromDsn($dsn);
    }

    public function requestPasswordRecovery(string $login, ?string $email = null): bool
    {
        $normalizedLogin = $this->normalize($login ?: $email);

        if ($normalizedLogin === '') {
            return true;
        }

        $user = $this->userRepository->findOneForPasswordRecovery($normalizedLogin);

        if (!$user instanceof User) {
            return true;
        }

        $token = $this->generateRecoveryToken();
        $user->setLostPassword($token);

        $this->manager->persist($user);
        $this->manager->flush();

        $this->sendRecoveryEmail($user, $token);

        return true;
    }

    public function validateRecoveryToken(string $token): bool
    {
        return $this->getUserByValidRecoveryToken($token) instanceof User;
    }

    public function resetPassword(string $token, string $password): bool
    {
        $user = $this->getUserByValidRecoveryToken($token);

        if (!$user instanceof User) {
            return false;
        }

        $user->setHash($this->passwordHasher->hashPassword($user, $password));
        $user->setLostPassword(null);
        $user->generateApiKey();

        $this->manager->persist($user);
        $this->manager->flush();

        return true;
    }

    private function getUserByValidRecoveryToken(string $token): ?User
    {
        $normalizedToken = trim($token);

        if ($normalizedToken === '' || !$this->isRecoveryTokenFresh($normalizedToken)) {
            return null;
        }

        $user = $this->userRepository->findOneByLostPassword($normalizedToken);

        if (!$user instanceof User) {
            return null;
        }

        if (!$user->getPeople()->getEnabled()) {
            return null;
        }

        return $user;
    }

    private function isRecoveryTokenFresh(string $token): bool
    {
        if (!preg_match(self::RECOVERY_TOKEN_PATTERN, $token, $matches)) {
            return false;
        }

        $issuedAt = DateTimeImmutable::createFromFormat(
            'YmdHis',
            $matches['issued_at'],
            new DateTimeZone('UTC')
        );

        if (!$issuedAt instanceof DateTimeImmutable) {
            return false;
        }

        $expiresAt = $issuedAt->add(new DateInterval(self::RECOVERY_TTL));
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return $expiresAt >= $now;
    }

    private function generateRecoveryToken(): string
    {
        $issuedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        return sprintf(
            '%s-%s',
            $issuedAt->format('YmdHis'),
            bin2hex(random_bytes(20))
        );
    }

    private function sendRecoveryEmail(User $user, string $token): void
    {
        $resetUrl = $this->buildResetUrl($token);
        $from = $this->resolveSenderEmail();

        $message = (new Email())
            ->from($from)
            ->to($user->getUsername())
            ->subject(sprintf('%s - Recuperação de senha', $this->appName))
            ->html($this->twig->render('email/lost-password.html.twig', [
                'app_name' => $this->appName,
                'reset_url' => $resetUrl,
                'token' => $token,
            ]));

        try {
            $this->mailer->send($message);
        } catch (TransportExceptionInterface $exception) {
            throw new RuntimeException('Não foi possível enviar o e-mail de recuperação.');
        }
    }

    private function buildResetUrl(string $token): string
    {
        $baseUrl = rtrim($this->resolveFrontendBaseUrl(), '/');

        return sprintf('%s/forgot-password/%s', $baseUrl, rawurlencode($token));
    }

    private function resolveFrontendBaseUrl(): string
    {
        $request = $this->requestStack->getCurrentRequest();
        $defaultScheme = $request?->getScheme() ?: 'https';

        $candidates = [
            $request?->headers->get('origin'),
            $request?->headers->get('referer'),
            $request?->headers->get('app-domain'),
            $request?->headers->get('App-Domain'),
            $request?->get('app-domain'),
            $request?->get('App-Domain'),
            $_ENV['PUBLIC_APP_DOMAIN'] ?? null,
            $_ENV['APP_DOMAIN'] ?? null,
            $_ENV['ADMIN_APP_DOMAIN'] ?? null,
            $_SERVER['PUBLIC_APP_DOMAIN'] ?? null,
            $_SERVER['APP_DOMAIN'] ?? null,
            $_SERVER['ADMIN_APP_DOMAIN'] ?? null,
            getenv('PUBLIC_APP_DOMAIN') ?: null,
            getenv('APP_DOMAIN') ?: null,
            getenv('ADMIN_APP_DOMAIN') ?: null,
        ];

        foreach ($candidates as $candidate) {
            $normalized = $this->normalizeBaseUrl($candidate, $defaultScheme);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        if ($request instanceof Request && $request->getHost()) {
            return sprintf('%s://%s', $defaultScheme, $request->getHost());
        }

        return sprintf('%s://app.controleonline.com', $defaultScheme);
    }

    private function normalizeBaseUrl(?string $value, string $defaultScheme): ?string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        if (!preg_match('#^[a-z][a-z0-9+.-]*://#i', $raw)) {
            $raw = sprintf('%s://%s', $defaultScheme, $raw);
        }

        $parts = parse_url($raw);

        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }

        $scheme = $parts['scheme'] ?? $defaultScheme;
        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return sprintf('%s://%s%s', $scheme, $parts['host'], $port);
    }

    private function resolveSenderEmail(): string
    {
        return $_ENV['REPORT_MAIL']
            ?? $_SERVER['REPORT_MAIL']
            ?? getenv('REPORT_MAIL')
            ?? 'no-reply@controleonline.com';
    }

    private function normalize(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}
