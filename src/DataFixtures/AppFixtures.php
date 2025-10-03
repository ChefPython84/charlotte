<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Salle;
use App\Entity\Reservation;
use App\Entity\Equipement;
use App\Entity\OptionService;
use App\Entity\Disponibilite;
use App\Entity\PhotoSalle;
use App\Entity\Facture;
use App\Entity\Paiement;
use App\Entity\Avis;
use App\Entity\Notification;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // -----------------------
        // USERS
        // -----------------------
        $users = [];
        // 1 admin + 2 gestionnaires + 7 clients
        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setNom($faker->lastName())
                 ->setPrenom($faker->firstName())
                 ->setEmail($faker->unique()->safeEmail())
                 ->setTelephone($faker->optional(0.8)->phoneNumber())
                 // motDePasse stocké hashed pour éviter erreurs d'authentification brute
                 ->setMotDePasse(password_hash('password', PASSWORD_BCRYPT))
                 ->setRole($i === 0 ? 'admin' : ($i < 3 ? 'gestionnaire' : 'client'))
                 ->setDateInscription($faker->dateTimeBetween('-2 years', 'now'));

            $manager->persist($user);
            $users[] = $user;
        }

        // -----------------------
        // EQUIPEMENTS
        // -----------------------
        $equipements = [];
        $equipList = ['Wifi', 'Projecteur', 'Climatisation', 'Cuisine équipée', 'Sonorisation', 'Micro HF', 'Ecran'];
        foreach ($equipList as $name) {
            $e = new Equipement();
            $e->setNom($name)
              ->setDescription($faker->optional(0.7)->sentence(8));
            $manager->persist($e);
            $equipements[] = $e;
        }

        // -----------------------
        // OPTION SERVICES
        // -----------------------
        $options = [];
        $optionList = [
            ['Service traiteur', 500.0],
            ['Ménage', 80.0],
            ['Décoration', 200.0],
            ['Sécurité', 300.0],
            ['Forfait son et lumière', 800.0],
        ];
        foreach ($optionList as [$label, $price]) {
            $opt = new OptionService();
            $opt->setNom($label)
                ->setPrix($price)
                ->setDescription($faker->optional(0.6)->sentence(12));
            $manager->persist($opt);
            $options[] = $opt;
        }

        // -----------------------
        // SALLES
        // -----------------------
        $salles = [];
        for ($i = 0; $i < 6; $i++) {
            $salle = new Salle();
            $salle->setNom("Salle " . ucfirst($faker->word()))
                  ->setDescription($faker->paragraph(2))
                  ->setCapacite($faker->numberBetween(30, 800))
                  ->setPrixJour($faker->randomFloat(2, 150, 5000))
                  ->setPrixHeure($faker->randomFloat(2, 20, 300))
                  ->setAdresse($faker->streetAddress())
                  ->setVille($faker->city())
                  ->setCodePostal($faker->postcode())
                  ->setStatut($faker->randomElement(['disponible','maintenance','fermee']));

            // associe quelques équipements
            $selectedEquip = $faker->randomElements($equipements, rand(2, 4));
            foreach ($selectedEquip as $eq) {
                $salle->addEquipement($eq);
                // on ne touche pas au côté inverse (Equipement->addSalle) pour éviter doublons/bugs
            }

            $manager->persist($salle);
            $salles[] = $salle;

            // Photos pour la salle
            // --- Photos pour la salle (utilise les fichiers présents dans public/uploads/salles/) ---
$uploadsDir = __DIR__ . '/../../public/uploads/salles'; // chemin côté serveur
$availableFiles = [];

// récupère tous les fichiers image du dossier
$glob = @glob($uploadsDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
if ($glob !== false) {
    $availableFiles = array_values($glob);
}

for ($p = 0; $p < 30; $p++) {
    $photo = new PhotoSalle();

    if (!empty($availableFiles)) {
        // choisit un fichier au hasard parmi ceux existants
        $chosen = $availableFiles[array_rand($availableFiles)];
        $filename = basename($chosen);
        $photoUrl = '/uploads/salles/' . $filename;
    } else {
        // fallback si pas de fichier trouvé
        $photoUrl = '/build/placeholder-room.png';
    }

    $photo->setSalle($salle)
          ->setUrl($photoUrl)
          ->setDescription($faker->optional(0.7)->sentence(6))
          ->setIsPrincipale($p === 0)
          ->setUploadedAt($faker->dateTimeBetween('-1 year', 'now'));

    $manager->persist($photo);
    $salle->addPhoto($photo);
}


            // Disponibilités (vacations) — on crée des créneaux sur les 3 prochains mois
            // Respecte le modèle heures: 08:00-13:00 / 14:00-19:00 / 20:00-00:00 / 00:00-03:00
            $vacations = [
                ['08:00','13:00'],
                ['14:00','19:00'],
                ['20:00','23:59'],
                ['00:00','03:00'],
            ];

            // génère 12 jours de disponibilités répartis
            for ($d = 0; $d < 12; $d++) {
                $baseDate = $faker->dateTimeBetween('now', '+3 months');
                // pick random vacation
                $vac = $faker->randomElement($vacations);
                $dateDebut = (clone $baseDate)->setTime(
                    intval(substr($vac[0], 0, 2)),
                    intval(substr($vac[0], 3, 2))
                );
                // dateFin = same day for most; if midnight or after, handle accordingly
                $dateFin = (clone $baseDate)->setTime(
                    intval(substr($vac[1], 0, 2)),
                    intval(substr($vac[1], 3, 2))
                );
                // if end < start then add one day (00:00-03:00 case)
                if ($dateFin <= $dateDebut) {
                    $dateFin->modify('+1 day');
                }

                $dispo = new Disponibilite();
                $dispo->setSalle($salle)
                      ->setDateDebut($dateDebut)
                      ->setDateFin($dateFin)
                      ->setHeureDebut($dateDebut)
                      ->setHeureFin($dateFin)
                      ->setStatut('libre');
                $manager->persist($dispo);
                $salle->addDisponibilite($dispo);
            }
        }

        // -----------------------
        // RESERVATIONS, FACTURES, PAIEMENTS, AVIS
        // -----------------------
        foreach ($salles as $salle) {
            // pour chaque salle, créer 2 à 6 réservations aléatoires
            $count = rand(2, 6);
            for ($r = 0; $r < $count; $r++) {
                $reservation = new Reservation();

                // dates cohérentes : dateDebut dans les 90 jours passés/avenir, durée 3-24h
                $start = $faker->dateTimeBetween('-2 months', '+3 months');
                $durationHours = rand(3, 24);
                $end = (clone $start)->modify("+{$durationHours} hours");

                // pick random user (client or any)
                $user = $faker->randomElement($users);

                $reservation->setUser($user)
                            ->setSalle($salle)
                            ->setDateDebut($start)
                            ->setDateFin($end)
                            ->setStatut($faker->randomElement(['en attente','confirmée','annulée']))
                            ->setPrixTotal($faker->randomFloat(2, 100, 5000));

                // ajouter 0..2 options
                foreach ($faker->randomElements($options, rand(0, 2)) as $opt) {
                    $reservation->addOption($opt);
                }

                $manager->persist($reservation);
                $salle->addReservation($reservation);

                // Facture pour la reservation
                $facture = new Facture();
                $facture->setReservation($reservation)
                        ->setMontant($reservation->getPrixTotal())
                        ->setStatut($faker->randomElement(['payée', 'en attente', 'partiellement payée']))
                        ->setDateFacture($faker->dateTimeBetween($start->modify('-10 days'), $start));
                $manager->persist($facture);
                $reservation->addFacture($facture);

                // Paiement parfois (si facture payée)
                if ($facture->getStatut() === 'payée') {
                    $paiement = new Paiement();
                    $paiement->setFacture($facture)
                             ->setMontant($facture->getMontant())
                             ->setMethode($faker->randomElement(['CB','Virement','PayPal']))
                             ->setDatePaiement($faker->dateTimeBetween($facture->getDateFacture(), $facture->getDateFacture()->modify('+10 days')))
                             ->setStatut('réussi');
                    $manager->persist($paiement);
                    $facture->addPaiement($paiement);
                }

                // Avis possible si confirmée and in the past
                if ($reservation->getStatut() === 'confirmée' && $reservation->getDateFin() < new \DateTime()) {
                    $avis = new Avis();
                    $avis->setUser($user)
                         ->setSalle($salle)
                         ->setNote($faker->numberBetween(1, 5))
                         ->setCommentaire($faker->optional(0.8)->sentence(12))
                         ->setDateAvis($faker->dateTimeBetween($reservation->getDateFin(), 'now'));
                    $manager->persist($avis);
                    $salle->addAvis($avis);
                    $user->addAvis($avis);
                }
            }
        }

        // -----------------------
        // NOTIFICATIONS
        // -----------------------
        foreach ($users as $user) {
            $n = rand(0, 3);
            for ($i = 0; $i < $n; $i++) {
                $notif = new Notification();
                $notif->setUser($user)
                      ->setMessage($faker->sentence(8))
                      ->setDateEnvoi($faker->dateTimeBetween('-30 days', 'now'))
                      ->setEstLu($faker->boolean(50));
                $manager->persist($notif);
            }
        }

        // flush final
        $manager->flush();
    }
}
