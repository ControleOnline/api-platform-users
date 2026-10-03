## Escopo
- Modulo de usuarios e autenticacao.
- Cobre `User`, recuperacao de senha, troca de senha, API key, autenticacao e seguranca.

## Quando usar
- Prompts sobre login backend, seguranca, token, usuario, senha, autenticador e fluxo de acesso.

## Regras de autenticacao
- `User` nao e a fonte de verdade dos roles; ele so carrega os roles resolvidos em runtime.
- Token e sessao devem usar a mesma resolucao de roles baseada em `people_link`.
- `ROLE_HUMAN` e apenas um agregador para guardas de entrada da API; ele nao deve ser persistido no usuario.
- `ROLE_SUPER` so aparece quando a pessoa autenticada for `owner` da empresa principal.

## Integracao com `people`
- A resolucao de roles vem de `PeopleRoleService`.
- `users` nao deve duplicar regra de vinculo, cadeia comercial ou escopo por empresa.
- `client`, `provider` e `franchisee` podem existir no token se vierem de vinculos diretos, mas nao substituem role humana operacional.

## Regras de autorizacao para `UserService`
- `UserService` deve ter `securityFilter` explicito ou mecanismo equivalente com efeito comprovavel para leitura e escrita de `User`.
- Filtro por query string, como `people=/people/{id}`, nao conta como autorizacao; o service precisa validar o escopo da pessoa autenticada sobre a entidade alvo.
- Ler `User` ou colecoes de `User` so e permitido para o proprio usuario ou para operador administrativo autorizado sobre a mesma pessoa/empresa; username, email e `apiKey` sao dados sensiveis.
- Criar usuario para uma `people`, trocar senha, renovar `apiKey` ou remover usuario so e permitido para operador autorizado sobre a `people` alvo. Receber `people` ou `user id` do cliente nunca e suficiente por si so.
- Fluxo de autoatendimento pode permitir troca de senha do proprio usuario autenticado, mas isso deve ser separado do fluxo administrativo e continuar restrito ao proprio titular.
- A exposicao de `apiKey` em resposta de leitura exige a mesma autorizacao forte do fluxo de renovacao; nao pode ficar acessivel a qualquer `ROLE_HUMAN`.
- Para a entidade `User`, "operador administrativo autorizado" significa apenas vinculos humanos administrativos da empresa alvo: `owner`, `director` ou `manager` (`PeopleLink::MANAGER_LINK`).
- Vinculos humanos nao administrativos, como `employee`, `salesman` e `after-sales`, nao podem ganhar leitura de colecao, leitura de outro usuario, criacao, exclusao, troca de senha nem renovacao de `apiKey` de terceiros apenas por compartilharem a mesma empresa.
- O proprio usuario autenticado pode ler apenas o proprio registro; qualquer leitura transversal dentro da empresa exige perfil administrativo.
- Se existir fluxo de autoatendimento para senha ou `apiKey`, ele deve operar somente sobre o proprio usuario autenticado e nao pode reaproveitar o mesmo criterio administrativo usado para gerir usuarios de terceiros.

## Limites
- Dados cadastrais de pessoa e empresa pertencem a `people`.
- Recorte de dados por empresa deve ficar nos `securityFilter` dos services de dominio.

## Documentação (navegação humana)

| Categoria | Destino |
| --- | --- |
| Home do módulo | https://github.com/ControleOnline/api-platform-users/wiki |
| Api-Home | https://github.com/ControleOnline/api-community/wiki |
| Wiki complementar (app) | https://github.com/ControleOnline/app-community/wiki |

### Por categoria — Fluxos de autenticação e e-mail

| Página | O que documenta |
| --- | --- |
| [Links de e-mail públicos (multi-tenant)](https://github.com/ControleOnline/api-platform-users/wiki/Links-de-email-publicos-multi-tenant) | Resolução de URL base para confirmação de conta e recuperação de senha; prioridade do domínio do tenant (request) sobre ENV; white-label |
| [POST /users — ROLE_HUMAN e vínculo people](https://github.com/ControleOnline/api-platform-users/wiki/POST-users-ROLE-HUMAN) | Security da criação de usuário; people em IRI; ligação com Client Details |

### Por categoria — Criação de usuário (Client Details)

| Página | O que documenta |
| --- | --- |
| [POST /users ROLE_HUMAN](https://github.com/ControleOnline/api-platform-users/wiki/POST-users-ROLE-HUMAN) | Contrato backend POST /users |
| Página canônica (UI) | https://github.com/ControleOnline/ui-customers/wiki/Client-Details-Criar-Usuario |

### Por categoria — Instalação

| Página | O que documenta |
| --- | --- |
| [Instalação](https://github.com/ControleOnline/api-platform-users/wiki/Instalacao) | Referências de instalação técnica do módulo |

### Módulos relacionados

| Módulo | Entrada |
| --- | --- |
| api-platform-common | https://github.com/ControleOnline/api-platform-common (DomainService) |
| api-community | https://github.com/ControleOnline/api-community/wiki |
| app-community | https://github.com/ControleOnline/app-community/wiki |
