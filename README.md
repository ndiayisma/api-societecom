# API Societecom : API REST protégée par OAuth2 / OpenID Connect (Keycloak)

L'API REST expose les unités légales du dump SIRENE (100 000 lignes, MySQL).
L'accès est protégé par **OAuth2** : chaque requête doit porter un **access token JWT** émis par le SSO
**Keycloak**. Le token est **signé en RS256**, donc avec une paire de clés **asymétrique** RSA :
Keycloak signe avec sa clé privée, et l'API vérifie avec la clé publique qu'elle récupère
via la discovery OIDC (`/.well-known/openid-configuration`, puis `jwks_uri`). L'API ne connaît donc aucun secret.

## Lancement (une commande)

```bash
docker compose up -d --build
```

Au premier démarrage, MySQL importe le dump (environ 1 minute) et Keycloak importe le realm `societecom`.
L'API attend que les deux soient prêts (`healthcheck`).

| Service  | URL                          | Accès                                |
|----------|------------------------------|--------------------------------------|
| API      | http://localhost:8001/api    | Bearer JWT                           |
| Keycloak | http://localhost:8080        | console admin : `admin` / `admin`    |
| MySQL    | localhost:3308               | `app` / `!ChangeMe!`                 |

## Ce que contient le realm Keycloak (`docker/keycloak/realm-societecom.json`)

- Realm `societecom`, signature des tokens **RS256**.
- Rôle realm `api-read`, obligatoire pour lire les unités légales.
- Client confidentiel `api-societecom-client` (secret `societecom-secret`) :
  - grant `client_credentials` (machine à machine, le service account a le rôle `api-read`) ;
  - grant `password` (démo) et `authorization_code` (navigateur, Postman) ;
  - mapper d'audience : `aud` contient `api-societecom` ;
  - mapper des rôles realm : `realm_access.roles`.
- Utilisateurs :
  - `demo` / `demo` a le rôle `api-read` ;
  - `noaccess` / `noaccess` n'a pas le rôle, donc 403.

## Endpoints

| Méthode | Route                              | Protection             |
|---------|------------------------------------|------------------------|
| GET     | `/api/health`                      | public                 |
| GET     | `/api/me`                          | token valide           |
| GET     | `/api/unites-legales?q=&page=&limit=` | rôle `api-read`     |
| GET     | `/api/unites-legales/{siren}`      | rôle `api-read`        |

## Tester

```bash
# 1. Obtenir un token (utilisateur demo)
TOKEN=$(curl -s -X POST http://localhost:8080/realms/societecom/protocol/openid-connect/token \
  -d grant_type=password -d client_id=api-societecom-client -d client_secret=societecom-secret \
  -d username=demo -d password=demo | jq -r .access_token)

#    ... ou en machine à machine :
#    -d grant_type=client_credentials -d client_id=api-societecom-client -d client_secret=societecom-secret

# 2. Appeler l'API
curl -H "Authorization: Bearer $TOKEN" http://localhost:8001/api/me
curl -H "Authorization: Bearer $TOKEN" "http://localhost:8001/api/unites-legales?q=BOULANGERIE&limit=5"
curl -H "Authorization: Bearer $TOKEN" http://localhost:8001/api/unites-legales/000325175

# Sans token, ou avec un token falsifié ou expiré : 401. Avec l'utilisateur noaccess : 403.
curl -i http://localhost:8001/api/unites-legales
```

PowerShell :

```powershell
$t = (Invoke-RestMethod -Method Post http://localhost:8080/realms/societecom/protocol/openid-connect/token -Body @{
  grant_type='password'; client_id='api-societecom-client'; client_secret='societecom-secret'; username='demo'; password='demo' }).access_token
Invoke-RestMethod http://localhost:8001/api/unites-legales?limit=3 -Headers @{ Authorization = "Bearer $t" }
```

Le token peut être inspecté sur https://jwt.io : l'en-tête contient `"alg": "RS256"` et le `kid` de la clé publique,
qui est publiée sur http://localhost:8080/realms/societecom/protocol/openid-connect/certs.

## Fonctionnement côté Symfony

- `config/packages/security.yaml` : firewall `api` (`^/api`) stateless, authenticator `access_token` avec
  le handler **`oidc`** de Symfony. Il vérifie la signature RS256 avec le JWKS découvert, ainsi que `iss`, `aud` et `exp`.
- `src/Security/KeycloakUserProvider.php` : convertit les rôles `realm_access.roles` en rôles Symfony
  (`api-read` devient `ROLE_API_READ`).
- `src/Controller/UniteLegaleController.php` : `#[IsGranted('ROLE_API_READ')]`.
- Variables d'environnement : `OIDC_ISSUER` (claim `iss` attendu), `OIDC_DISCOVERY_URI` (URL interne de
  Keycloak, `http://keycloak:8080/...` dans Docker) et `OIDC_AUDIENCE`.

Keycloak est configuré avec `KC_HOSTNAME=http://localhost:8080` et `KC_HOSTNAME_BACKCHANNEL_DYNAMIC=true`.
L'issuer est donc toujours `http://localhost:8080/realms/societecom`, même quand l'API joint Keycloak
par le réseau Docker interne.

## Réinitialiser

```bash
docker compose down -v && docker compose up -d --build
```
