<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Salle;
use App\Repository\DisponibiliteRepository; // AJOUT
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        DisponibiliteRepository $dispoRepo // AJOUT
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

        // --- GESTION POST : Soumettre la réservation ---
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

            $dispo = $dispoRepo->find($dispo_id);

            // Double vérification (au cas où qqn l'a réservé entre temps)
            if (!$dispo || $dispo->getSalle() !== $salle || $dispo->getStatut() !== 'libre') {
                 return $this->json(['success'=>false, 'message'=>'Ce créneau n\'est plus disponible.'], 400);
            }

            // 1. On crée la nouvelle réservation
            $reservation = new Reservation();
            $reservation->setSalle($salle)
                        ->setUser($user)
                        ->setDateDebut($dispo->getDateDebut()) // Date du créneau
                        ->setDateFin($dispo->getDateFin())   // Date du créneau
                        ->setStatut('en attente')  // Nouveau statut (sera validé par l'admin)
                        ->setPrixTotal(0); // L'admin définira le prix

            // 2. On met à jour le créneau pour le passer en "réservé"
            $dispo->setStatut('reserve');

            $em->persist($reservation);
            $em->persist($dispo); // On persiste les deux
            $em->flush();

            // On rafraîchira le calendrier côté client
            return $this->json(['success'=>true, 'message' => 'Pré-réservation enregistrée.']);
        }

        return $this->redirectToRoute('salle_show', ['id'=>$salle->getId()]);
    }
}