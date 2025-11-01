<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted; // <-- Sécuriser

#[Route('/notifications')]
#[IsGranted('ROLE_USER')] // <-- Sécuriser tout le contrôleur
class NotificationController extends AbstractController
{
    #[Route('/', name: 'app_notifications')]
    public function index(NotificationRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // --- CORRECTION ---
        // On utilise findBy (standard) et on trie par date décroissante
        $notifications = $repo->findBy(
            ['user' => $user],
            ['dateEnvoi' => 'DESC'] 
        );
        // --- FIN CORRECTION ---

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/mark-read/{id}', name: 'app_notification_mark_read')]
    public function markRead(int $id, NotificationRepository $repo, EntityManagerInterface $em): Response
    {
        $notif = $repo->find($id);

        // On vérifie bien que la notif appartient à l'utilisateur connecté
        if ($notif && $notif->getUser() === $this->getUser()) {
            $notif->setEstLu(true);
            $em->flush();
        }

        // On redirige vers la page des notifications
        return $this->redirectToRoute('app_notifications');
    }
}