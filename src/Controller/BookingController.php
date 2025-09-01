<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Venue;
use App\Form\BookingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class BookingController extends AbstractController
{
    #[Route('/venue/{id}/booking/new', name: 'booking_new')]
    public function new(Venue $venue, Request $request, EntityManagerInterface $em): Response
    {
        $start = $request->query->get('start');
        $end = $request->query->get('end');

        $booking = new Booking();
        $booking->setVenue($venue)
                ->setStartDate(new \DateTime($start))
                ->setEndDate(new \DateTime($end))
                ->setStatus('draft');

        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($booking);
            $em->flush();

            $this->addFlash('success', 'Réservation enregistrée avec succès !');

            return $this->redirectToRoute('venue_show', ['id' => $venue->getId()]);
        }

        return $this->render('booking/new.html.twig', [
            'form' => $form->createView(),
            'venue' => $venue,
        ]);
    }
}
