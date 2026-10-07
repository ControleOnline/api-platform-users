# OAuth for remote MCP clients

The users module provides OAuth Authorization Code with PKCE for public MCP clients. Each person signs in with their own ControleOnline account and approves the `mcp:read` scope in the manager application. The access token is bound to the tenant selected during authorization and expires after 15 minutes.

OAuth metadata is published at `/.well-known/oauth-authorization-server`. The server supports client registration at `POST /oauth/register`, authorization at `GET /oauth/authorize`, code exchange at `POST /oauth/token`, and token revocation at `POST /oauth/revoke`.

To revoke an access token, a public client sends its `token` and signed `client_id` to `/oauth/revoke`. The server invalidates the token immediately. Invalid or mismatched revocation requests return success without disclosing token state, following RFC 7009 behavior. Clients should discard the token after revocation.

The module authenticates the user for each MCP request. The MCP provider must still re-check accessible companies and invoke each domain's `securityFilter`; an OAuth scope does not grant access to additional companies or fields.

For multi-tenancy, MCP clients may address a tenant with `/mcp/{tenant-domain}`, for example `https://api.controleonline.com/mcp/app.controleonline.com`. The server advertises a path-specific protected-resource document; the OAuth client passes its `resource` value through authorization and consent. The server selects that tenant before login, then binds it into the signed token. Each MCP request checks the URL domain against the token before the database switch. A mismatch returns `403 tenant_mismatch`; it cannot move a token into another tenant. If the path is just `/mcp`, the server selects the API `mainDomain` and requires the token to be bound to that same domain. Client-supplied `app-domain`, `Origin`, and `Referer` values do not choose the tenant.

Authorization clients may omit `scope`; the server then requests the configured least-privilege default (`mcp:read`). Explicit scopes are still checked against the configured allowlist. Invalid authorization requests return a safe `error_description` so clients can identify which required OAuth parameter needs correction.
