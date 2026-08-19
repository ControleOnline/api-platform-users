<?php

namespace ControleOnline\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use ControleOnline\Entity\User;
use ControleOnline\Service\UserService;
use Doctrine\ORM\QueryBuilder;

/**
 * Applies UserService::securityFilter on User collection/item queries.
 * Required by AGENTS contract and app-community#95 security review.
 */
class UserSecurityExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly UserService $userService,
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->apply($queryBuilder, $resourceClass);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = []
    ): void {
        $this->apply($queryBuilder, $resourceClass);
    }

    private function apply(QueryBuilder $queryBuilder, string $resourceClass): void
    {
        if ($resourceClass !== User::class) {
            return;
        }

        $rootAlias = $queryBuilder->getRootAliases()[0] ?? null;
        $this->userService->securityFilter(
            $queryBuilder,
            $resourceClass,
            'api_platform',
            $rootAlias
        );
    }
}
