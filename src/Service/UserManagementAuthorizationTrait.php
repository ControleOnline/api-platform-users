<?php

namespace ControleOnline\Service;

use ControleOnline\Entity\People;
use ControleOnline\Entity\PeopleLink;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait UserManagementAuthorizationTrait
{
    private function denyUnlessCanManagePeople(People $people): void
    {
        if ($this->canManagePeople($people)) {
            return;
        }

        throw new AccessDeniedHttpException('You should not pass!!!');
    }

    private function canManagePeople(People $people): bool
    {
        $tokenUser = $this->security->getToken()?->getUser();
        if (is_object($tokenUser) && method_exists($tokenUser, 'getRoles') && in_array('ROLE_SUPER', $tokenUser->getRoles() ?: [], true)) {
            return true;
        }

        $myPeople = $this->getMyPeople();
        if (!$myPeople instanceof People) {
            return false;
        }

        // Operator may manage their own people record (self-service / profile update path).
        if ((int) $myPeople->getId() === (int) $people->getId()) {
            return true;
        }

        $managedCompanyIds = array_map(
            static fn(People $company): int => (int) $company->getId(),
            array_filter(
                $this->getManagedCompanies(),
                fn(People $company): bool => $this->isPeopleEnabled($company)
            )
        );

        if ($managedCompanyIds === []) {
            return false;
        }

        // Target may be the company itself (PJ just created / company profile update).
        if (in_array((int) $people->getId(), $managedCompanyIds, true)) {
            return true;
        }

        $targetCompanyIds = $this->getCompanyIdsForPeople($people);
        if ($targetCompanyIds === []) {
            return false;
        }

        return array_intersect($managedCompanyIds, $targetCompanyIds) !== [];
    }

    private function getCompanyIdsForPeople(People $people): array
    {
        $companyIds = [];

        foreach ($people->getLink() as $link) {
            if (!$link instanceof PeopleLink || !$this->isLinkEnabled($link)) {
                continue;
            }

            $company = $link->getCompany();
            if (!$company instanceof People || !$this->isPeopleEnabled($company)) {
                continue;
            }

            $companyIds[] = (int) $company->getId();
        }

        return array_values(array_unique(array_filter($companyIds)));
    }

    private function isLinkEnabled(PeopleLink $link): bool
    {
        if (!method_exists($link, 'getEnabled')) {
            return true;
        }

        return (bool) $link->getEnabled();
    }

    private function isPeopleEnabled(People $people): bool
    {
        if (!method_exists($people, 'getEnabled')) {
            return true;
        }

        return (bool) $people->getEnabled();
    }


}
