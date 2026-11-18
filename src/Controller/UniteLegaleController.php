<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * API REST de consultation des unités légales (table `mytable`, dump SIRENE).
 * Toutes les routes exigent un access token Keycloak portant le rôle realm `api-read`.
 */
#[Route('/api/unites-legales', name: 'api_unites_legales_')]
#[IsGranted('ROLE_API_READ')]
final class UniteLegaleController extends AbstractController
{
    private const COLUMNS = 'siren, date_creation_unite_legale, prenom_unite_legale, nom_unite_legale,
        denomination_unite_legale, tranche_effectifs_unite_legale, etat_administratif_unite_legale,
        activite_principale_unite_legale';

    public function __construct(private readonly Connection $db)
    {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = min(100, max(1, $request->query->getInt('limit', 20)));
        $q = trim((string) $request->query->get('q', ''));

        $qb = $this->db->createQueryBuilder()->from('mytable');
        if ('' !== $q) {
            $qb->where('denomination_unite_legale LIKE :q OR nom_unite_legale LIKE :q')
                ->setParameter('q', '%'.addcslashes($q, '%_\\').'%');
        }

        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(*)')->fetchOne();
        $items = $qb->select(self::COLUMNS)
            ->orderBy('siren')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->fetchAllAssociative();

        return $this->json([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'items' => array_map($this->format(...), $items),
        ]);
    }

    #[Route('/{siren}', name: 'show', requirements: ['siren' => '\d{9}'], methods: ['GET'])]
    public function show(string $siren): JsonResponse
    {
        $row = $this->db->createQueryBuilder()
            ->select(self::COLUMNS)->from('mytable')
            ->where('siren = :siren')->setParameter('siren', $siren)
            ->fetchAssociative();

        if (false === $row) {
            return $this->json(['error' => 'Unité légale introuvable'], 404);
        }

        return $this->json($this->format($row));
    }

    private function format(array $r): array
    {
        return [
            'siren' => $r['siren'],
            'denomination' => $r['denomination_unite_legale'],
            'nom' => $r['nom_unite_legale'],
            'prenom' => $r['prenom_unite_legale'],
            'dateCreation' => $r['date_creation_unite_legale'],
            'trancheEffectifs' => $r['tranche_effectifs_unite_legale'],
            'etatAdministratif' => $r['etat_administratif_unite_legale'],
            'activitePrincipale' => $r['activite_principale_unite_legale'],
        ];
    }
}
