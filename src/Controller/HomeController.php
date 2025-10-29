<?php
// src/Controller/HomeController.php
namespace App\Controller;

use App\Repository\SalleRepository;
// On supprime les autres repositories qui ne servent qu'aux stats
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        Request $request,
        SalleRepository $salleRepo
        // On supprime tous les autres repos (Reservation, Avis, Facture...)
    ): Response {
        
        // --- SUPPRESSION DE TOUT LE BLOC STATS ---
        
        // --- paramètres de recherche / filtres (méthode GET) ---
        $q = trim($request->query->get('q', ''));
        $ville = trim($request->query->get('ville', ''));
        $capacite = $request->query->getInt('capacite', 0);
        $prixMax = $request->query->get('prix_max', null);
        $sort = $request->query->get('sort', 'recent'); // recent | prix_asc | prix_desc | capacite

        // --- construire requête depuis le repository (QueryBuilder) ---
        $qb = $salleRepo->createQueryBuilder('s');

        if ($q !== '') {
            $qb->andWhere('LOWER(s.nom) LIKE :q OR LOWER(s.description) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        if ($ville !== '') {
            $qb->andWhere('LOWER(s.ville) = :ville')
               ->setParameter('ville', mb_strtolower($ville));
        }

        if ($capacite > 0) {
            $qb->andWhere('s.capacite >= :capacite')
               ->setParameter('capacite', $capacite);
        }

        if ($prixMax !== null && $prixMax !== '') {
            // prixJour comparé (on suppose prixJour stored as string/decimal in DB)
            $qb->andWhere('s.prixJour <= :prixMax')
               ->setParameter('prixMax', (float)$prixMax);
        }

        switch ($sort) {
            case 'prix_asc':
                $qb->orderBy('s.prixJour', 'ASC');
                break;
            case 'prix_desc':
                $qb->orderBy('s.prixJour', 'DESC');
                break;
            case 'capacite':
                $qb->orderBy('s.capacite', 'DESC');
                break;
            case 'recent':
            default:
                $qb->orderBy('s.id', 'DESC');
                break;
        }

        $salles = $qb->getQuery()->getResult();

        // featured : les 3 premières salles (si tu veux autre logique, change ici)
        $featured = array_slice($salles, 0, 3);

        return $this->render('home/index.html.twig', [
            'salles' => $salles,
            'featured' => $featured,
            
            // --- SUPPRESSION DES VARIABLES STATS ---
            // 'totalSalles' => $totalSalles,
            // 'totalReservations' => $totalReservations,
            // 'totalAvis' => $totalAvis,
            // 'totalUsers' => $totalUsers,
            // 'totalFactures' => $totalFactures,
            // 'totalNotifications' => $totalNotifications,
            
            'q' => $q,
            'ville' => $ville,
            'capacite' => $capacite,
            'prixMax' => $prixMax,
            'sort' => $sort,
        ]);
    }
}