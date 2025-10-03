<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\Salle;
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

    #[Route('/salle/{id}/reservations', name: 'salle_reservations', methods: ['GET'])]
    public function eventsJson(Salle $salle): JsonResponse
    {
        $reservations = $salle->getReservations();
        $events = [];

        foreach ($reservations as $r) {
            $color = match($r->getStatut()) {
                'en attente' => 'orange',
                'confirmée'  => 'green',
                'annulée'    => 'red',
                default      => 'blue',
            };
            $events[] = [
                'title' => $r->getUser() ? $r->getUser()->getNom() : 'Réservé',
                'start' => $r->getDateDebut()->format('Y-m-d\TH:i:s'),
                'end'   => $r->getDateFin()->format('Y-m-d\TH:i:s'),
                'color' => $color,
            ];
        }

        return $this->json($events);
    }

    #[Route('/salle/{id}/reservation/new', name: 'reservation_new', methods: ['GET','POST'])]
    public function new(Request $request, Salle $salle, EntityManagerInterface $em): Response
    {
        if ($request->isXmlHttpRequest() && $request->isMethod('GET')) {
            return $this->render('reservation/_form_modal.html.twig', [
                'salle' => $salle,
                'start' => $request->query->get('start'),
                'end'   => $request->query->get('end'),
            ]);
        }

        if ($request->isXmlHttpRequest() && $request->isMethod('POST')) {
            $start = $request->request->get('start');
            $end   = $request->request->get('end');

            $user = $this->getUser();
            if (!$user) {
                return $this->json(['success'=>false, 'message'=>'Vous devez être connecté'],403);
            }

            if (!$start || !$end) {
                return $this->json(['success'=>false, 'message'=>'Dates manquantes'],400);
            }

            $reservation = new Reservation();
            $reservation->setSalle($salle)
                        ->setUser($user)
                        ->setDateDebut(new \DateTime($start))
                        ->setDateFin(new \DateTime($end))
                        ->setStatut('en attente')
                        ->setPrixTotal(0);

            $em->persist($reservation);
            $em->flush();

            return $this->json(['success'=>true]);
        }

        return $this->redirectToRoute('salle_show', ['id'=>$salle->getId()]);
    }
}
