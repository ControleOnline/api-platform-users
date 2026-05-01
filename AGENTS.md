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

## Limites
- Dados cadastrais de pessoa e empresa pertencem a `people`.
- Recorte de dados por empresa deve ficar nos `securityFilter` dos services de dominio.

## Recuperacao de senha
- `UserService` precisa ter `securityFilter` real para leitura e escrita de `User`; placeholders como `getPermission() => true` nao contam como protecao valida.
- Fluxo publico de recuperacao so pode alterar credencial depois de prova de posse do fator de recuperacao; enviar e-mail por si so nao autoriza escrita.
- Se o requisito funcional falar em senha temporaria com login obrigatorio e troca posterior, a implementacao precisa cumprir exatamente esse comportamento ou o requisito precisa ser ajustado explicitamente. Link de redefinicao com expiracao nao e equivalente por padrao.
- Em qualquer variante, expiracao, revogacao e limpeza de estado antigo devem acontecer no backend e nao apenas na interface.
- Credenciais temporarias, hashes de recuperacao e marcadores de expiracao nao podem vazar por grupos amplos de serializacao.
