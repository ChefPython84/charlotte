<?php

namespace App\Twig;

use App\Repository\NotificationRepository;
use Symfony\Bundle\SecurityBundle\Security; // Pour récupérer l'utilisateur
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    private NotificationRepository $notifRepo;
    private Security $security;

    public function __construct(NotificationRepository $notifRepo, Security $security)
    {
        $this->notifRepo = $notifRepo;
        $this->security = $security;
    }

    public function getFunctions(): array
    {
        return [
            // Déclare une nouvelle fonction Twig : get_unread_notifications()
            new TwigFunction('get_unread_notifications', [$this, 'getUnreadNotifications']),
        ];
    }

    /**
     * Récupère les notifications non lues de l'utilisateur connecté
     */
    public function getUnreadNotifications(): array
    {
        $user = $this->security->getUser();
        if (!$user) {
            return [];
        }

        return $this->notifRepo->findBy(
            ['user' => $user, 'estLu' => false],
            ['dateEnvoi' => 'DESC']
        );
    }
}