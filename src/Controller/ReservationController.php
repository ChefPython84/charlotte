<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Salle;
use App\Repository\DisponibiliteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\LockMode; // <-- AJOUTÉ

class ReservationController extends AbstractController
{
    #[Route('/salle/{id}', name: 'salle_show', methods: ['GET'])]
    public function show(Salle $salle): Response
    {
        return $this->render('salle/show.html.twig', [
            'salle' => $salle
        ]);
    }

    /**
     * MODIFIÉ : Renvoie les créneaux "Disponibles" (statut 'libre')
     * au lieu des réservations existantes.
     */
    #[Route('/salle/{id}/reservations', name: 'salle_reservations', methods: ['GET'])]
    public function eventsJson(Salle $salle, DisponibiliteRepository $dispoRepo): JsonResponse
    {
        // On ne cherche que les créneaux "libres"
        $disponibilites = $dispoRepo->findBy([
            'salle' => $salle,
            'statut' => 'libre'
        ]);
        
        $events = [];

        foreach ($disponibilites as $dispo) {
            $events[] = [
                'id'    => $dispo->getId(), // Crucial pour le clic
                'title' => 'Disponible',
                'start' => $dispo->getDateDebut()->format('Y-m-d\TH:i:s'),
                'end'   => $dispo->getDateFin()->format('Y-m-d\TH:i:s'),
                'color' => 'green', // On affiche les créneaux libres en vert
            ];
        }

        return $this->json($events);
    }

    /**
     * MODIFIÉ : Gère la réservation via un ID de Disponibilité
     */
    #[Route('/salle/{id}/reservation/new', name: 'reservation_new', methods: ['GET','POST'])]
    public function new(
        Request $request, 
        Salle $salle, 
        EntityManagerInterface $em,
        DisponibiliteRepository $dispoRepo
    ): Response {

        // --- GESTION GET : Afficher la modale ---
        if ($request->isXmlHttpRequest() && $request->isMethod('GET')) {
            
            // On récupère l'ID du créneau cliqué
            $dispo_id = $request->query->get('dispo_id');
            if (!$dispo_id) {
                return $this->json(['success'=>false, 'message'=>'ID de créneau manquant'], 400);
            }
            
            $dispo = $dispoRepo->find($dispo_id);
            
            // Vérification
            if (!$dispo || $dispo->getSalle() !== $salle || $dispo->getStatut() !== 'libre') {
                 return $this->json(['success'=>false, 'message'=>'Ce créneau n\'est pas valide ou n\'est plus disponible.'], 404);
            }

            // On envoie le créneau au template de la modale
            return $this->render('reservation/_form_modal.html.twig', [
                'salle' => $salle,
                'dispo' => $dispo,
            ]);
        }

        // --- GESTION POST : Soumettre la réservation (MODIFIÉ AVEC TRANSACTION ET LOCK) ---
        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            
            $user = $this->getUser();
            if (!$user) {
                return $this->json(['success'=>false, 'message'=>'Vous devez être connecté'], 403);
            }

            // On récupère l'ID du créneau depuis le formulaire
            $dispo_id = $request->request->get('disponibiliteId');
            if (!$dispo_id) {
                return $this->json(['success'=>false, 'message'=>'ID de créneau manquant'], 400);
            }

            // Démarrer une transaction
            $em->beginTransaction();
            try {
                // 1. Trouver le créneau ET LE VERROUILLER pour écriture
                $dispo = $dispoRepo->find($dispo_id, LockMode::PESSIMISTIC_WRITE);

                // 2. RE-VÉRIFIER le statut APRES avoir obtenu le verrou
                if (!$dispo || $dispo->getSalle() !== $salle || $dispo->getStatut() !== 'libre') {
                     // L'autre utilisateur a été plus rapide
                     throw new \Exception('Ce créneau n\'est plus disponible.');
                }

                // 3. Le créneau est à nous, on le réserve
                $reservation = new Reservation();
                $reservation->setSalle($salle)
                            ->setUser($user)
                            ->setDateDebut($dispo->getDateDebut()) // Date du créneau
                            ->setDateFin($dispo->getDateFin())   // Date du créneau
                            ->setStatut('en_attente')  // Statut initial du workflow
                            ->setPrixTotal(0); // L'admin définira le prix

                // 4. On met à jour le créneau pour le passer en "réservé"
                $dispo->setStatut('reserve');

                $em->persist($reservation);
                // $em->persist($dispo); // Pas nécessaire, $dispo est déjà managé par l'EM
                
                $em->flush(); // Appliquer les changements
                $em->commit(); // Libérer le verrou

                return $this->json(['success'=>true, 'message' => 'Pré-réservation enregistrée.']);

            } catch (\Exception $e) {
                $em->rollback(); // Annuler la transaction en cas d'erreur
                return $this->json(['success'=>false, 'message' => $e->getMessage()], 400);
            }
        }
        // --- FIN DE LA MODIFICATION ---

        return $this->redirectToRoute('salle_show', ['id'=>$salle->getId()]);
    }
}