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

## Limites
- Dados cadastrais de pessoa e empresa pertencem a `people`.
- Recorte de dados por empresa deve ficar nos `securityFilter` dos services de dominio.

## Regras de seguranca para `User`
- `UserService` deve possuir `securityFilter` real e material para leitura e escrita da entidade `User`.
- Placeholder como `getPermission() => true` nao conta como autorizacao valida para criar usuario, trocar senha, girar `apiKey` ou remover usuario.
- Leitura transversal e administracao de usuarios de terceiros devem ficar restritas a perfis administrativos explicitamente comprovados no contexto da empresa alvo.
- O proprio usuario pode atuar apenas sobre o proprio acesso nos fluxos de autoatendimento explicitamente previstos.

## Regras de recuperacao de senha
- O pedido publico de recuperacao nao pode alterar senha nem outra credencial antes de prova de posse do fator de recuperacao.
- Quando a regra de negocio pedir senha temporaria com login obrigatorio e troca posterior, esse comportamento precisa ser implementado de forma explicita; um fluxo de link para redefinir senha nao e equivalente por padrao.
- Se o produto optar por fluxo de link temporario, essa decisao precisa estar alinhada com o requisito da issue e documentada de forma clara antes da aprovacao de seguranca.
- Tokens de recuperacao devem expirar, ser de uso limitado e ser limpos ao concluir com sucesso ou ao expirar.
