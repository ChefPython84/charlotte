<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\SalleRepository;

class SalleController extends AbstractController
{
    #[Route('/salles', name: 'salle_index', methods: ['GET'])]
    public function index(SalleRepository $repo): Response
    {
    $salles = $repo->findAll();
    return $this->render('salle/index.html.twig', [
        'salles' => $salles,
    ]);
    }
}