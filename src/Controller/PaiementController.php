<?php

namespace App\Controller;

use App\Entity\Paiement;
use App\Repository\PaiementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/paiements')]
class PaiementController extends AbstractController
{
    #[Route('/', name: 'paiement_index')]
    public function index(PaiementRepository $repo): Response
    {
        $user = $this->getUser();

        $paiements = $repo->createQueryBuilder('p')
            ->join('p.facture', 'f')
            ->join('f.reservation', 'r')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.datePaiement', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('paiement/index.html.twig', [
            'paiements' => $paiements,
        ]);
    }
}
