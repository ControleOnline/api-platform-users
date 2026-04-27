## Escopo
- Modulo de usuarios e autenticacao.
- Cobre `User`, recuperacao de senha, troca de senha, API key, autenticacao e seguranca.

## Quando usar
- Prompts sobre login backend, seguranca, token, usuario, senha, autenticador e fluxo de acesso.

## Limites
- Dados cadastrais de pessoa e empresa pertencem a `people`.
- `users` deve cuidar de credencial, autenticacao e identidade de acesso.

## Regras de negocio
- O endpoint publico `POST /users/create-account` deve continuar aceitando o payload simples do front web (`name`, `email`, `password` e `confirmPassword`) e retornar a sessao pronta para login imediato.
