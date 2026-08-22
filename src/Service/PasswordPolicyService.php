<?php

namespace ControleOnline\Service;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Canonical password policy for create / change / recovery flows.
 * Rules: minimum length + not compromised (Have I Been Pwned).
 * Messages are Portuguese product copy — do not expose raw English validator text.
 */
class PasswordPolicyService
{
    public const MIN_LENGTH = 6;

    public const MSG_REQUIRED = 'A senha é obrigatória.';
    public const MSG_MIN_LENGTH = 'A senha precisa ter pelo menos %d caracteres.';
    public const MSG_COMPROMISED = 'Esta senha não pode ser usada porque consta em lista de senhas comprometidas (vazamento de dados). Escolha outra senha.';
    public const MSG_CONFIRM_MISMATCH = 'A senha e a confirmação devem ser iguais.';

    public function __construct(
        private ?ValidatorInterface $validator = null
    ) {}

    public function getMinLength(): int
    {
        return self::MIN_LENGTH;
    }

    public function getHelpLines(): array
    {
        return [
            sprintf('Mínimo de %d caracteres.', self::MIN_LENGTH),
            'Não use senhas que já tenham vazado em vazamentos públicos de dados.',
        ];
    }

    /**
     * Validate plaintext password against the product policy.
     * Throws BadRequestHttpException with PT message on failure.
     */
    public function assertValid(?string $password, ?string $confirm = null): void
    {
        $normalized = is_string($password) ? trim($password) : '';

        if ($normalized === '') {
            throw new BadRequestHttpException(self::MSG_REQUIRED);
        }

        if (mb_strlen($normalized) < self::MIN_LENGTH) {
            throw new BadRequestHttpException(
                sprintf(self::MSG_MIN_LENGTH, self::MIN_LENGTH)
            );
        }

        if ($confirm !== null) {
            $confirmNormalized = is_string($confirm) ? trim($confirm) : '';
            if ($normalized !== $confirmNormalized) {
                throw new BadRequestHttpException(self::MSG_CONFIRM_MISMATCH);
            }
        }

        if (!$this->validator instanceof ValidatorInterface) {
            return;
        }

        $violations = $this->validator->validate(
            $normalized,
            [
                new Assert\NotCompromisedPassword(
                    message: self::MSG_COMPROMISED
                ),
            ]
        );

        if (count($violations) > 0) {
            $message = (string) $violations[0]->getMessage();
            if ($this->looksLikeEnglishBreachMessage($message)) {
                $message = self::MSG_COMPROMISED;
            }
            throw new BadRequestHttpException($message);
        }
    }

    /**
     * Map any raw validator / API message to product PT copy when it is a known policy violation.
     */
    public function mapErrorMessage(string $raw): string
    {
        $text = trim($raw);
        if ($text === '') {
            return $text;
        }

        if ($this->looksLikeEnglishBreachMessage($text) || stripos($text, 'compromised') !== false) {
            return self::MSG_COMPROMISED;
        }

        if (
            preg_match('/at least\s+(\d+)\s+characters/i', $text, $m)
            || preg_match('/pelo menos\s+(\d+)\s+caracteres/i', $text, $m)
        ) {
            return sprintf(self::MSG_MIN_LENGTH, (int) $m[1]);
        }

        if (stripos($text, 'must be identical') !== false || stripos($text, 'não coincidem') !== false) {
            return self::MSG_CONFIRM_MISMATCH;
        }

        return $text;
    }

    private function looksLikeEnglishBreachMessage(string $text): bool
    {
        return (bool) preg_match(
            '/leaked|data breach|compromised password|have i been pwned/i',
            $text
        );
    }
}
