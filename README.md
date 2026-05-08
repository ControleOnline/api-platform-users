[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/controleonline/api-platform-users/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/controleonline/api-platform-users/?branch=master)

# users


`composer require controleonline/users:dev-master`



Add Service import:
config\services.yaml

```yaml
imports:
    - { resource: "../modules/controleonline/orders/tasks/services/tasks.yaml" }    
```

Change your autentication file:
config\packages\security.yaml

```yaml
security:
    encoders:
        ControleOnline\Entity\User:
            algorithm: bcrypt
    providers:
        app_user_provider:
            entity:
                class: ControleOnline\Entity\User
    firewalls:
        dev:
            pattern : ^/(_(profiler|wdt)|css|images|js)/
            security: false
        main:
            stateless : true
            anonymous : lazy
            provider  : app_user_provider
            json_login:
                check_path   : /token
                username_path: username
                password_path: password
            guard:
                authenticators:
                    - App\Security\TokenAuthenticator
    role_hierarchy:
        ROLE_SUPER: ROLE_SUPER
        ROLE_OWNER: ROLE_OWNER
        ROLE_DIRECTOR: ROLE_DIRECTOR
        ROLE_MANAGER: ROLE_MANAGER
        ROLE_SALESMAN: ROLE_SALESMAN
        ROLE_AFTER_SALES: ROLE_AFTER_SALES
        ROLE_EMPLOYEE: ROLE_EMPLOYEE
        ROLE_CLIENT: ROLE_CLIENT
        ROLE_PROVIDER: ROLE_PROVIDER
        ROLE_FRANCHISEE: ROLE_FRANCHISEE
        ROLE_HUMAN: ROLE_HUMAN

    access_control:
        - { path: ^/my_contracts/signatures-finished, roles: PUBLIC_ACCESS, requires_channel: https }

```

And create a file:
App\Security\TokenAuthenticator

```php
<?php

namespace ControleOnline\Security;

use ControleOnline\Security\TokenAuthenticator as SecurityTokenAuthenticator;

class TokenAuthenticator extends SecurityTokenAuthenticator
{
    
}
```

## Password recovery flow

The public password recovery request no longer changes the user password immediately.

Current behavior:
- the initial request generates temporary recovery tokens and sends the recovery e-mail
- recovery tokens expire after 15 minutes
- the password only changes when the recovery flow is completed with a valid, non-expired token
- expired or successfully used recovery tokens are cleared after completion

Validation:
- focused PHPUnit coverage lives in `tests/Service/PasswordRecoveryServiceTest.php`
- the branch workflow `Pull Request Checks` is the canonical automated evidence for this flow in review branches

## Development quality checks

This module now keeps its CI bootstrap inside the repository so pull requests can publish reproducible validation evidence without relying on global tools.

Current checks:
- run `composer install` before local validation so `php_codesniffer` is available from `vendor/bin`
- run `composer lint` to apply the repository `phpcs.xml` ruleset to `src`
- pull requests to `master` trigger the `Pull Request Checks` workflow
- the workflow validates `composer.json`, installs dependencies and runs `tools/phpcs-changed-files.sh` only against the PHP files changed in the branch

Notes:
- incremental linting is intentional and avoids failing the branch because of legacy style debt outside the current diff
- `.scrutinizer.yml` stays focused on Scrutinizer analysis and no longer forces a full module `phpcs` execution
