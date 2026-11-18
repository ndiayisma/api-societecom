<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\OidcUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MeController extends AbstractController
{
    /** Renvoie l'identité extraite du JWT (utile pour vérifier le token). */
    #[Route('/api/me', name: 'api_me', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED')]
    public function me(): JsonResponse
    {
        /** @var OidcUser $user */
        $user = $this->getUser();

        return $this->json([
            'username' => $user->getUserIdentifier(),
            'sub' => $user->getSub(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
        ]);
    }

    /** Route publique (sans token) pour vérifier que l'API répond. */
    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        return $this->json(['status' => 'ok']);
    }
}
