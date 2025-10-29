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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface; 

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher; 

    public function __construct(UserPasswordHasherInterface $passwordHasher) 
    {
        $this->passwordHasher = $passwordHasher; 
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // -----------------------
        // USERS
        // -----------------------
        $users = [];
        
        // --- ADMIN ---
        $admin = new User();
        $admin->setEmail('admin@test.com')
              ->setNom('Admin')
              ->setPrenom('User')
              ->setMotDePasse($this->passwordHasher->hashPassword($admin, 'password'))
              ->setRole(User::ROLE_ADMIN) // Utilise la constante de l'entité User
              ->setDateInscription($faker->dateTimeBetween('-2 years', 'now'));
        $manager->persist($admin);
        $users[] = $admin;

        // --- MAIRIE (L'AJOUT MANQUANT) ---
        $mairieUser = new User();
        $mairieUser->setEmail('mairie@test.com')
                   ->setNom('Service')
                   ->setPrenom('Mairie')
                   ->setMotDePasse($this->passwordHasher->hashPassword($mairieUser, 'password'))
                   ->setRole(User::ROLE_MAIRIE) // Le rôle Mairie
                   ->setDateInscription($faker->dateTimeBetween('-1 year', 'now'));
        $manager->persist($mairieUser);
        $users[] = $mairieUser;
        
        // --- GESTIONNAIRES ---
        for ($i = 0; $i < 2; $i++) {
             $gestionnaire = new User();
             $gestionnaire->setEmail($faker->unique()->safeEmail())
                   ->setNom($faker->lastName())
                   ->setPrenom('Gestionnaire')
                   ->setMotDePasse($this->passwordHasher->hashPassword($gestionnaire, 'password'))
                   ->setRole(User::ROLE_GESTIONNAIRE)
                   ->setDateInscription($faker->dateTimeBetween('-1 year', 'now'));
            $manager->persist($gestionnaire);
            $users[] = $gestionnaire;
        }

        // --- CLIENTS ---
        for ($i = 0; $i < 7; $i++) {
            $client = new User();
            $client->setEmail($faker->unique()->safeEmail())
                   ->setNom($faker->lastName())
                   ->setPrenom($faker->firstName())
                   ->setMotDePasse($this->passwordHasher->hashPassword($client, 'password'))
                   ->setRole(User::ROLE_CLIENT) // Utilise la constante
                   ->setDateInscription($faker->dateTimeBetween('-1 year', 'now'));
            $manager->persist($client);
            $users[] = $client;
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
                  ->setStatut($faker->randomElement(['disponible','maintenance'])); 

             $selectedEquip = $faker->randomElements($equipements, rand(2, 4));
            foreach ($selectedEquip as $eq) {
                $salle->addEquipement($eq);
            }

            $manager->persist($salle);
            $salles[] = $salle;

            // Création Photos...
            $uploadsDir = __DIR__ . '/../../public/uploads/salles'; 
            $availableFiles = [];
            $glob = @glob($uploadsDir . '/*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
            if ($glob !== false) { $availableFiles = array_values($glob); }
            for ($p = 0; $p < 5; $p++) { 
                $photo = new PhotoSalle();
                $photoUrl = !empty($availableFiles) ? '/uploads/salles/' . basename($availableFiles[array_rand($availableFiles)]) : '/build/placeholder-room.png';
                $photo->setSalle($salle)
                      ->setUrl($photoUrl)
                      ->setDescription($faker->optional(0.7)->sentence(6))
                      ->setIsPrincipale($p === 0)
                      ->setUploadedAt($faker->dateTimeBetween('-1 year', 'now'));
                $manager->persist($photo);
            }


            // Création Disponibilite...
            $vacations = [['08:00','13:00'], ['14:00','19:00'], ['20:00','23:59']];
            for ($d = 0; $d < 8; $d++) { 
                $baseDate = $faker->dateTimeBetween('now', '+1 months'); 
                $vac = $faker->randomElement($vacations);
                $dateDebut = (clone $baseDate)->setTime( intval(substr($vac[0], 0, 2)), intval(substr($vac[0], 3, 2)) );
                $dateFin = (clone $baseDate)->setTime( intval(substr($vac[1], 0, 2)), intval(substr($vac[1], 3, 2)) );
                
                $dispo = new Disponibilite();
                $dispo->setSalle($salle)
                      ->setDateDebut($dateDebut)
                      ->setDateFin($dateFin)
                      ->setStatut('libre'); // **IMPORTANT** : Doit être 'libre'
                $manager->persist($dispo);
            }
        }


        // -----------------------
        // RESERVATIONS, FACTURES, PAIEMENTS, AVIS
        // -----------------------
        foreach ($salles as $salle) {
            $count = rand(1, 3); 
            for ($r = 0; $r < $count; $r++) {
                $reservation = new Reservation();

                $start = $faker->dateTimeBetween('-1 months', '+1 months');
                $durationHours = rand(3, 8);
                $end = (clone $start)->modify("+{$durationHours} hours");
                $user = $faker->randomElement($users);

                // Utilise les statuts valides du workflow
                $statut = $faker->randomElement([
                    'en_attente', 
                    'attente_dossier_client', 
                    'contrat_genere', 
                    'annulee' 
                ]);

                $reservation->setUser($user)
                            ->setSalle($salle)
                            ->setDateDebut($start)
                            ->setDateFin($end)
                            ->setStatut($statut) 
                            ->setPrixTotal($faker->randomFloat(2, 100, 2000));

                foreach ($faker->randomElements($options, rand(0, 1)) as $opt) {
                    $reservation->addOption($opt);
                }

                $manager->persist($reservation);

                // Création Facture, Paiement, Avis...
                 if (in_array($statut, ['contrat_genere', 'contrat_signe'])) { 
                    $facture = new Facture();
                    $factureStatut = $faker->randomElement(['payée', 'en attente']);
                    $facture->setReservation($reservation)
                            ->setMontant($reservation->getPrixTotal() ?? 0)
                            ->setStatut($factureStatut) 
                            ->setDateFacture($faker->dateTimeBetween($start->modify('-5 days'), $start));
                    $manager->persist($facture);

                    if ($factureStatut === 'payée') {
                         $paiement = new Paiement();
                         $paiement->setFacture($facture)
                                  ->setMontant($facture->getMontant())
                                  ->setMethode($faker->randomElement(['CB','Virement']))
                                  ->setDatePaiement($faker->dateTimeBetween($facture->getDateFacture(), $facture->getDateFacture()->modify('+5 days')))
                                  ->setStatut('réussi');
                         $manager->persist($paiement);
                    }
                }
                
                 if ($statut === 'contrat_signe' && $end < new \DateTime()) { 
                    $avis = new Avis();
                    $avis->setUser($user)
                         ->setSalle($salle)
                         ->setNote($faker->numberBetween(3, 5))
                         ->setCommentaire($faker->optional(0.8)->sentence(10))
                         ->setDateAvis($faker->dateTimeBetween($end, '+5 days'));
                    $manager->persist($avis);
                 }
            }
        }

        // -----------------------
        // NOTIFICATIONS
        // -----------------------
        foreach ($users as $user) {
            $n = rand(0, 2);
            for ($i = 0; $i < $n; $i++) {
                $notif = new Notification();
                $notif->setUser($user)
                      ->setMessage($faker->sentence(8))
                      ->setDateEnvoi($faker->dateTimeBetween('-15 days', 'now'))
                      ->setEstLu($faker->boolean(30));
                $manager->persist($notif);
            }
        }

        // flush final
        $manager->flush();
    }
}