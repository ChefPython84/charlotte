<?php

namespace App\DataFixtures;

use App\Entity\Municipality;
use App\Entity\Venue;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // --- Mairies ---
        $municipalities = [];

        $municipalityData = [
            ['name' => 'Mairie de Lyon', 'address' => '1 Place de la Comédie, 69001 Lyon', 'email' => 'contact@lyon.fr', 'phone' => '0472000000'],
            ['name' => 'Mairie de Grenoble', 'address' => 'Place de l\'Hôtel de Ville, 38000 Grenoble', 'email' => 'contact@grenoble.fr', 'phone' => '0387000000'],
            ['name' => 'Mairie de Annecy', 'address' => 'Place de l\'Hôtel de Ville, 74000 Annecy', 'email' => 'contact@annecy.fr', 'phone' => '0450000000'],
        ];

        foreach ($municipalityData as $data) {
            $m = new Municipality();
            $m->setName($data['name'])
              ->setAddress($data['address'])
              ->setEmail($data['email'])
              ->setPhone($data['phone']);
            $manager->persist($m);
            $municipalities[] = $m;
        }

        // --- Salles ---
        $venuesData = [
            ['name' => 'Salle des Fêtes Lyon Centre', 'municipality' => $municipalities[0], 'description' => 'Grande salle avec scène et sono', 'capacity' => 150, 'address' => '12 Rue Centrale, Lyon', 'price' => 500.00, 'pictures' => ['https://via.placeholder.com/400x200']],
            ['name' => 'Salle Polyvalente Grenoble', 'municipality' => $municipalities[1], 'description' => 'Salle spacieuse avec tables et chaises', 'capacity' => 100, 'address' => '5 Avenue des Alpes, Grenoble', 'price' => 350.00, 'pictures' => ['https://via.placeholder.com/400x200']],
            ['name' => 'Salle des Fêtes Annecy', 'municipality' => $municipalities[2], 'description' => 'Salle avec cuisine et bar', 'capacity' => 120, 'address' => '20 Quai de l\'Hôtel de Ville, Annecy', 'price' => 400.00, 'pictures' => ['https://via.placeholder.com/400x200']],
        ];

        foreach ($venuesData as $data) {
            $v = new Venue();
            $v->setName($data['name'])
              ->setMunicipality($data['municipality'])
              ->setDescription($data['description'])
              ->setCapacity($data['capacity'])
              ->setAddress($data['address'])
              ->setPrice($data['price'])
              ->setPictures($data['pictures']);
            $manager->persist($v);
        }

        $manager->flush();
    }
}
