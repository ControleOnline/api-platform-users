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
        ROLE_ADMIN : ROLE_ADMIN
        ROLE_ADMIN : ROLE_CLIENT
        ROLE_CLIENT: ROLE_CLIENT

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

## Public create-account compatibility

The module keeps the current public signup flow at `POST /create-account` and also accepts the legacy web route `POST /users/create-account`.

### Accepted payload

```json
{
  "name": "Maria Silva",
  "email": "maria@example.com",
  "password": "secret123",
  "confirmPassword": "secret123"
}
```

### Behaviour

- both public routes create the account through the same action
- a successful request returns the session payload expected by the web login flow
- invalid public payloads now return `400 Bad Request` instead of an internal server error

### Test coverage

- `tests/Controller/CreateAccountActionTest.php`
- GitHub Actions workflow: `Pull Request Checks`
