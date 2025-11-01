<?php

namespace App\Controller;

use App\Entity\Facture;
use App\Repository\FactureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted; // Pour la sécurité de base

// --- AJOUT IMPORTANT ---
// Cet 'use' est nécessaire pour la sécurité avancée
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security; 
// --- FIN AJOUT ---

#[Route('/factures')]
#[IsGranted('ROLE_USER')] // Sécurise tout le contrôleur (il faut être connecté)
class FactureController extends AbstractController
{
    /**
     * Cette route n'est probablement pas utilisée car votre page /compte 
     * affiche déjà la liste. On la garde pour la complétude.
     */
    #[Route('/', name: 'facture_index')]
    public function index(FactureRepository $repo): Response
    {
        // Redirige simplement vers l'espace compte
        return $this->redirectToRoute('app_compte');
    }

    /**
     * Affiche UNE facture pour le client.
     */
    #[Route('/{id}', name: 'facture_show')]
    // --- C'EST LA CORRECTION ---
    // Cette règle de sécurité vérifie si :
    // 1. L'utilisateur a le ROLE_ADMIN
    //    OU
    // 2. L'utilisateur connecté (user) est le MÊME que le propriétaire 
    //    (getReservation().getUser()) de la facture (subject).
    #[Security("is_granted('ROLE_ADMIN') or subject.getReservation().getUser() == user", subject: 'facture')]
    // --- FIN DE LA CORRECTION ---
    public function show(Facture $facture): Response
    {
        // Si la sécurité #[Security] passe, on affiche le template CLIENT.
        // On n'utilise plus $this->denyAccessUnlessGranted()
        
        return $this->render('facture/show.html.twig', [
            'facture' => $facture,
        ]);
    }
}