<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/notifications')]
class NotificationController extends AbstractController
{
    #[Route('/', name: 'app_notifications')]
    public function index(NotificationRepository $repo): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $notifications = $repo->findByUser($user);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
        ]);
    }

    #[Route('/mark-read/{id}', name: 'app_notification_mark_read')]
    public function markRead(int $id, NotificationRepository $repo, EntityManagerInterface $em): Response
    {
        $notif = $repo->find($id);

        if ($notif && $notif->getUser() === $this->getUser()) {
            $notif->setEstLu(true);
            $em->flush();
        }

        return $this->redirectToRoute('app_notifications');
    }
}
