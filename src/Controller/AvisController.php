<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Salle;
use App\Form\AvisType;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/avis')]
class AvisController extends AbstractController
{
    #[Route('/salle/{id}', name: 'app_avis_salle')]
    public function list(Salle $salle, AvisRepository $repo): Response
    {
        $avis = $repo->findBySalle($salle);
        $noteMoyenne = $repo->getAverageNoteBySalle($salle);

        return $this->render('avis/list.html.twig', [
            'salle' => $salle,
            'avis' => $avis,
            'noteMoyenne' => $noteMoyenne,
        ]);
    }

    #[Route('/salle/{id}/ajouter', name: 'app_avis_ajouter')]
    public function add(Salle $salle, Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $avis = new Avis();
        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avis->setSalle($salle)
                 ->setUser($user)
                 ->setDateAvis(new \DateTime());

            $em->persist($avis);
            $em->flush();

            $this->addFlash('success', 'Avis ajouté avec succès !');
            return $this->redirectToRoute('app_avis_salle', ['id' => $salle->getId()]);
        }

        return $this->render('avis/add.html.twig', [
            'salle' => $salle,
            'form' => $form->createView(),
        ]);
    }
}
