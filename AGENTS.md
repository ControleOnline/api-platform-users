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
- Preferencias operacionais do login que pertencem ao usuario autenticado, como fuso horario, devem ficar em `User` e sair no payload de sessao/login.

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

## Limites
- Dados cadastrais de pessoa e empresa pertencem a `people`.
- Recorte de dados por empresa deve ficar nos `securityFilter` dos services de dominio.

## Recuperacao de senha
- `UserService` precisa ter `securityFilter` real para leitura e escrita de `User`; placeholders como `getPermission() => true` nao contam como protecao valida.
- Fluxo publico de recuperacao so pode alterar credencial depois de prova de posse do fator de recuperacao; enviar e-mail por si so nao autoriza escrita.
- Se o requisito funcional falar em senha temporaria com login obrigatorio e troca posterior, a implementacao precisa cumprir exatamente esse comportamento ou o requisito precisa ser ajustado explicitamente. Link de redefinicao com expiracao nao e equivalente por padrao.
- Em qualquer variante, expiracao, revogacao e limpeza de estado antigo devem acontecer no backend e nao apenas na interface.
- Credenciais temporarias, hashes de recuperacao e marcadores de expiracao nao podem vazar por grupos amplos de serializacao.
