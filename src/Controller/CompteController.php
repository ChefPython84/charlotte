<?php

namespace App\Controller;

use App\Form\CompteProfilFormType;
use App\Form\ComptePasswordFormType;
use App\Repository\ReservationRepository;
use App\Repository\FactureRepository; // <-- Assurez-vous que c'est bien le bon Repository
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/compte')]
#[IsGranted('ROLE_USER')] // Sécurité sur tout le contrôleur
class CompteController extends AbstractController
{
    /**
     * Page principale de l'espace client (Tableau de bord)
     */
    #[Route('', name: 'app_compte')]
    public function index(
        ReservationRepository $reservationRepo,
        FactureRepository $factureRepo // <-- On a besoin de lui pour le QueryBuilder
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // 1. Récupérer les réservations (cette requête était correcte)
        $reservations = $reservationRepo->findBy(
            ['user' => $user],
            ['dateDebut' => 'DESC']
        );

        // --- DÉBUT DE LA CORRECTION ---
        // 2. Récupérer les factures via une jointure (QueryBuilder)
        // On cherche les Factures (f) dont la réservation (r) a pour utilisateur (:user)
        $factures = $factureRepo->createQueryBuilder('f')
            ->join('f.reservation', 'r') // 'f.reservation' est la propriété dans l'entité Facture
            ->where('r.user = :user')     // 'r.user' est la propriété dans l'entité Reservation
            ->setParameter('user', $user)
            ->orderBy('f.dateFacture', 'DESC')
            ->getQuery()
            ->getResult();
        // --- FIN DE LA CORRECTION ---

        // 3. Envoyer les données au template
        return $this->render('compte/index.html.twig', [
            'reservations' => $reservations,
            'factures' => $factures, // Cette variable est maintenant correcte
        ]);
    }

    /**
     * Page "Modifier mon profil"
     */
    #[Route('/profil', name: 'app_compte_profil')]
    public function profil(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(CompteProfilFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Votre profil a été mis à jour avec succès.');
            return $this->redirectToRoute('app_compte_profil');
        }

        return $this->render('compte/profil.html.twig', [
            'profilForm' => $form->createView(),
        ]);
    }

    /**
     * Page "Changer mon mot de passe"
     */
    #[Route('/changer-mot-de-passe', name: 'app_compte_password')]
    public function changePassword(
        Request $request, 
        EntityManagerInterface $em, 
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $form = $this->createForm(ComptePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Le champ 'currentPassword' est validé par 'UserPassword' constraint
            
            /** @var string $newPassword */
            $newPassword = $form->get('newPassword')->getData();
            
            $user->setMotDePasse(
                $passwordHasher->hashPassword($user, $newPassword)
            );
            
            $em->flush();

            $this->addFlash('success', 'Votre mot de passe a été modifié avec succès.');
            return $this->redirectToRoute('app_compte'); // Redirige vers le tableau de bord
        }

        return $this->render('compte/change_password.html.twig', [
            'passwordForm' => $form->createView(),
        ]);
    }
}