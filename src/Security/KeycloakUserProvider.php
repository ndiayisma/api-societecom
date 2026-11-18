<?php

namespace App\Security;

use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\AttributesBasedUserProviderInterface;
use Symfony\Component\Security\Core\User\OidcUser;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Construit l'utilisateur à partir des claims du JWT déjà vérifié (signature RS256, iss, aud, exp)
 * par le handler OIDC de Symfony. Les rôles realm Keycloak (`realm_access.roles`) deviennent
 * des rôles Symfony : `api-read` => `ROLE_API_READ`.
 *
 * @implements AttributesBasedUserProviderInterface<OidcUser>
 */
final class KeycloakUserProvider implements AttributesBasedUserProviderInterface
{
    public function loadUserByIdentifier(string $identifier, array $attributes = []): UserInterface
    {
        $roles = ['ROLE_USER'];
        foreach ($attributes['realm_access']['roles'] ?? [] as $role) {
            $roles[] = 'ROLE_'.strtoupper(str_replace('-', '_', $role));
        }

        return new OidcUser(
            userIdentifier: $identifier,
            roles: array_values(array_unique($roles)),
            sub: $attributes['sub'] ?? null,
            name: $attributes['name'] ?? null,
            preferredUsername: $attributes['preferred_username'] ?? null,
            email: $attributes['email'] ?? null,
        );
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        // Firewall stateless : jamais appelé.
        throw new UnsupportedUserException();
    }

    public function supportsClass(string $class): bool
    {
        return OidcUser::class === $class;
    }
}
