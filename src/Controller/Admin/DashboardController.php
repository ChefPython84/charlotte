<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Entity\Salle;
use App\Entity\Reservation;
use App\Entity\Facture;
use App\Repository\SalleRepository;
use App\Repository\ReservationRepository;
use App\Repository\AvisRepository;
use App\Repository\FactureRepository;
use App\Repository\NotificationRepository;
use App\Repository\UserRepository;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    private SalleRepository $salleRepo;
    private ReservationRepository $reservationRepo;
    private AvisRepository $avisRepo;
    private FactureRepository $factureRepo;
    private NotificationRepository $notificationRepo;
    private UserRepository $userRepo;

    public function __construct(
        SalleRepository $salleRepo,
        ReservationRepository $reservationRepo,
        AvisRepository $avisRepo,
        FactureRepository $factureRepo,
        NotificationRepository $notificationRepo,
        UserRepository $userRepo
    ) {
        $this->salleRepo = $salleRepo;
        $this->reservationRepo = $reservationRepo;
        $this->avisRepo = $avisRepo;
        $this->factureRepo = $factureRepo;
        $this->notificationRepo = $notificationRepo;
        $this->userRepo = $userRepo;
    }

    /**
     * Route d'accès au dashboard admin.
     * IMPORTANT: la signature doit être index(): Response pour rester compatible avec EasyAdmin.
     */
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Totaux rapides
        $totalSalles = $this->salleRepo->count([]);
        $totalReservations = $this->reservationRepo->count([]);
        $totalAvis = $this->avisRepo->count([]);
        $totalUsers = $this->userRepo->count([]);
        $totalFactures = $this->factureRepo->count([]);
        $totalNotifications = $this->notificationRepo->count([]);

        // Réservations récentes (10)
        $recentReservations = $this->reservationRepo->findBy([], ['dateDebut' => 'DESC'], 10);

        // Préparer les données du graphique : réservations par mois (12 derniers mois)
        $startDate = (new \DateTime())->modify('first day of this month')->setTime(0,0)->modify('-11 months');

        $resFromDb = $this->reservationRepo->createQueryBuilder('r')
            ->where('r.dateDebut >= :start')
            ->setParameter('start', $startDate)
            ->orderBy('r.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();

        $map = [];
        foreach ($resFromDb as $r) {
            $dt = $r->getDateDebut();
            if (!$dt) continue;
            $key = $dt->format('Y-m');
            if (!isset($map[$key])) $map[$key] = 0;
            $map[$key]++;
        }

        $labels = [];
        $data = [];
        $cursor = clone $startDate;
        for ($i = 0; $i < 12; $i++) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->format('M Y');
            $data[] = $map[$key] ?? 0;
            $cursor->modify('+1 month');
        }

        return $this->render('admin/dashboard.html.twig', [
            'totalSalles' => $totalSalles,
            'totalReservations' => $totalReservations,
            'totalAvis' => $totalAvis,
            'totalUsers' => $totalUsers,
            'totalFactures' => $totalFactures,
            'totalNotifications' => $totalNotifications,
            'recentReservations' => $recentReservations,
            'chartLabels' => $labels,
            'chartData' => $data,
            'salles' => $this->salleRepo->findAll(),
        ]);
    }

    /**
     * Configure le titre du dashboard (EasyAdmin).
     */
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Espace 1500 — Administration');
    }

    /**
     * Menu de l'admin.
     * Remarque : EasyAdmin requiert l'existence d'un CrudController pour chaque Entity
     * passée à linkToCrud(...) sinon erreur "Unable to find the controller related to the Entity".
     */
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');

        // Si tu as créé les CrudControllers (recommandé) : utiliser linkToCrud
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-user', User::class);
        yield MenuItem::linkToCrud('Salles', 'fa fa-building', Salle::class);
        yield MenuItem::linkToCrud('Réservations', 'fa fa-calendar', Reservation::class);
        yield MenuItem::linkToCrud('Factures', 'fa fa-receipt', Facture::class);

        // Liens supplémentaires directs (routes standard du site)
        yield MenuItem::linkToRoute('Voir le site', 'fa fa-eye', 'home');
        yield MenuItem::linkToRoute('Notifications (front)', 'fa fa-bell', 'app_notifications');
        yield MenuItem::section('Paramètres');
    }
}
