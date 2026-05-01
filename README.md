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

## User management scope

The user-management flow used by the manager area now keeps all sensitive operations on the same guarded service path.

Current behavior:
- direct user reads and management stay restricted by `UserService::securityFilter`
- cross-person management is limited to `owner`, `director`, and `manager`
- disabled `people_link` rows and disabled companies no longer authorize reads, deletes, password changes, or API key rotation
- `DELETE /users/{id}` now follows the same guarded `UserService` path used by create, password-change, and API-key rotation flows

Validation:
- focused coverage lives in `tests/Service/UserServiceTest.php`, `tests/Controller/DeleteUserActionTest.php`, and `tests/Entity/UserSerializationGroupsTest.php`
- the branch workflow `Pull Request Checks` is the canonical automated evidence for this flow in review branches
