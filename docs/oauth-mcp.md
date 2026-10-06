# OAuth for remote MCP clients

The users module provides OAuth Authorization Code with PKCE for public MCP clients. Each person signs in with their own ControleOnline account and approves the `mcp:read` scope in the manager application. The access token is bound to the tenant selected during authorization and expires after 15 minutes.

OAuth metadata is published at `/.well-known/oauth-authorization-server`. The server supports client registration at `POST /oauth/register`, authorization at `GET /oauth/authorize`, code exchange at `POST /oauth/token`, and token revocation at `POST /oauth/revoke`.

To revoke an access token, a public client sends its `token` and signed `client_id` to `/oauth/revoke`. The server invalidates the token immediately. Invalid or mismatched revocation requests return success without disclosing token state, following RFC 7009 behavior. Clients should discard the token after revocation.

The module authenticates the user for each MCP request. The MCP provider must still re-check accessible companies and invoke each domain's `securityFilter`; an OAuth scope does not grant access to additional companies or fields.
