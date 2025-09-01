<?php

namespace App\Controller\Api;

use App\Entity\Booking;
use App\Entity\Venue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/bookings')]
class BookingApiController extends AbstractController
{
    #[Route('/{id}', name: 'api_bookings', methods: ['GET'])]
    public function index(Venue $venue, EntityManagerInterface $em): JsonResponse
    {
        $bookings = $em->getRepository(Booking::class)->findBy(['venue' => $venue]);

        $events = [];
        foreach ($bookings as $b) {
            $events[] = [
                'title' => ucfirst($b->getStatus()),
                'start' => $b->getStartDate()->format('Y-m-d\TH:i:s'),
                'end' => $b->getEndDate()->format('Y-m-d\TH:i:s'),
            ];
        }

        return $this->json($events);
    }
}
