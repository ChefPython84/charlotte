<?php

namespace App\Controller;

use App\Entity\Venue;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VenueController extends AbstractController
{
    #[Route('/', name: 'venue_index')]
    public function index(EntityManagerInterface $em): Response
    {
        $venues = $em->getRepository(Venue::class)->findAll();

        return $this->render('venue/index.html.twig', [
            'venues' => $venues,
        ]);
    }

    #[Route('/venue/{id}', name: 'venue_show')]
    public function show(Venue $venue): Response
    {
        return $this->render('venue/show.html.twig', [
            'venue' => $venue,
        ]);
    }
}
