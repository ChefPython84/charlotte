<?php

namespace App\Controller;

use App\Entity\Facture;
use App\Repository\FactureRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/factures')]
class FactureController extends AbstractController
{
    #[Route('/', name: 'facture_index')]
    public function index(FactureRepository $repo): Response
    {
        $user = $this->getUser();

        $factures = $repo->createQueryBuilder('f')
            ->join('f.reservation', 'r')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('f.dateFacture', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('facture/index.html.twig', [
            'factures' => $factures,
        ]);
    }

    #[Route('/{id}', name: 'facture_show')]
    public function show(Facture $facture): Response
    {
        $this->denyAccessUnlessGranted('view', $facture);

        return $this->render('facture/show.html.twig', [
            'facture' => $facture,
        ]);
    }
}
