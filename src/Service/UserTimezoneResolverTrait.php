<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\Timezone;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

trait UserTimezoneResolverTrait
{
    private const DEFAULT_TIMEZONE_NAME = 'America/Sao_Paulo';

    private function resolveTimezoneForUserCreate(array $payload): Timezone
    {
        if (
            array_key_exists('timezone', $payload) ||
            array_key_exists('timezone_id', $payload) ||
            array_key_exists('timezoneId', $payload)
        ) {
            $timezone = $this->resolveTimezoneFromPayload($payload);
            if ($timezone instanceof Timezone) {
                return $timezone;
            }
        }

        return $this->resolveDefaultTimezone();
    }

    private function resolveDefaultTimezone(): Timezone
    {
        $repository = $this->manager->getRepository(Timezone::class);
        $timezone = $repository->findOneBy(['name' => self::DEFAULT_TIMEZONE_NAME])
            ?: $repository->findOneBy(['name' => 'UTC']);

        if (!$timezone instanceof Timezone) {
            throw new BadRequestHttpException('timezone is required');
        }

        return $timezone;
    }

    private function resolveTimezoneFromPayload(array $payload): ?Timezone
    {
        $rawTimezone =
            $payload['timezone'] ??
            $payload['timezone_id'] ??
            $payload['timezoneId'] ??
            null;

        if ($rawTimezone === null || $rawTimezone === '') {
            return null;
        }

        if (is_int($rawTimezone) || (is_string($rawTimezone) && preg_match('#^\d+$#', trim($rawTimezone)))) {
            $timezone = $this->manager->getRepository(Timezone::class)->find((int) $rawTimezone);
            return $timezone instanceof Timezone ? $timezone : null;
        }

        if (is_string($rawTimezone) && preg_match('#^/timezones/(\d+)$#', trim($rawTimezone), $m)) {
            $timezone = $this->manager->getRepository(Timezone::class)->find((int) $m[1]);
            return $timezone instanceof Timezone ? $timezone : null;
        }

        if (is_string($rawTimezone)) {
            $timezone = $this->manager->getRepository(Timezone::class)->findOneBy(['name' => trim($rawTimezone)]);
            return $timezone instanceof Timezone ? $timezone : null;
        }

        return null;
    }


}
