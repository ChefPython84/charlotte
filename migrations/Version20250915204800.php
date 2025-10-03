<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250915204800 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, salle_id INT NOT NULL, note INT NOT NULL, commentaire LONGTEXT DEFAULT NULL, date_avis DATETIME NOT NULL, INDEX IDX_8F91ABF0A76ED395 (user_id), INDEX IDX_8F91ABF0DC304035 (salle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE disponibilite (id INT AUTO_INCREMENT NOT NULL, salle_id INT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, heure_debut TIME DEFAULT NULL, heure_fin TIME DEFAULT NULL, statut VARCHAR(50) NOT NULL, INDEX IDX_2CBACE2FDC304035 (salle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE equipement (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE facture (id INT AUTO_INCREMENT NOT NULL, reservation_id INT NOT NULL, montant NUMERIC(10, 2) NOT NULL, statut VARCHAR(50) NOT NULL, date_facture DATETIME NOT NULL, INDEX IDX_FE866410B83297E7 (reservation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, message VARCHAR(255) NOT NULL, date_envoi DATETIME NOT NULL, est_lu TINYINT(1) NOT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE option_service (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prix NUMERIC(10, 2) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE paiement (id INT AUTO_INCREMENT NOT NULL, facture_id INT NOT NULL, date_paiement DATETIME NOT NULL, montant NUMERIC(10, 2) NOT NULL, methode VARCHAR(50) NOT NULL, statut VARCHAR(50) NOT NULL, INDEX IDX_B1DC7A1E7F2DEE08 (facture_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE photo_salle (id INT AUTO_INCREMENT NOT NULL, salle_id INT NOT NULL, url VARCHAR(255) NOT NULL, description VARCHAR(255) DEFAULT NULL, is_principale TINYINT(1) NOT NULL, uploaded_at DATETIME NOT NULL, INDEX IDX_F4C679EBDC304035 (salle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reservation (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, salle_id INT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, prix_total NUMERIC(10, 2) DEFAULT NULL, type_manifestation VARCHAR(5) DEFAULT NULL, vacations JSON DEFAULT NULL, documents JSON DEFAULT NULL, dossier_submitted_at DATETIME DEFAULT NULL, contract_created_at DATETIME DEFAULT NULL, contract_signed_at DATETIME DEFAULT NULL, INDEX IDX_42C84955A76ED395 (user_id), INDEX IDX_42C84955DC304035 (salle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reservation_option_service (reservation_id INT NOT NULL, option_service_id INT NOT NULL, INDEX IDX_EC5DCC9EB83297E7 (reservation_id), INDEX IDX_EC5DCC9E35B1D6 (option_service_id), PRIMARY KEY(reservation_id, option_service_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE salle (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(150) NOT NULL, description LONGTEXT NOT NULL, capacite INT NOT NULL, prix_jour NUMERIC(10, 2) NOT NULL, prix_heure NUMERIC(10, 2) NOT NULL, adresse VARCHAR(255) NOT NULL, ville VARCHAR(100) NOT NULL, code_postal VARCHAR(10) NOT NULL, statut VARCHAR(50) NOT NULL, vacations JSON DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE salle_equipement (salle_id INT NOT NULL, equipement_id INT NOT NULL, INDEX IDX_D338336BDC304035 (salle_id), INDEX IDX_D338336B806F0F5C (equipement_id), PRIMARY KEY(salle_id, equipement_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, prenom VARCHAR(100) NOT NULL, email VARCHAR(180) NOT NULL, telephone VARCHAR(20) DEFAULT NULL, mot_de_passe VARCHAR(255) NOT NULL, role VARCHAR(50) NOT NULL, type_organisateur VARCHAR(50) DEFAULT NULL, siret VARCHAR(14) DEFAULT NULL, rna VARCHAR(50) DEFAULT NULL, commune VARCHAR(150) DEFAULT NULL, date_inscription DATETIME NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0DC304035 FOREIGN KEY (salle_id) REFERENCES salle (id)');
        $this->addSql('ALTER TABLE disponibilite ADD CONSTRAINT FK_2CBACE2FDC304035 FOREIGN KEY (salle_id) REFERENCES salle (id)');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_FE866410B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE paiement ADD CONSTRAINT FK_B1DC7A1E7F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id)');
        $this->addSql('ALTER TABLE photo_salle ADD CONSTRAINT FK_F4C679EBDC304035 FOREIGN KEY (salle_id) REFERENCES salle (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C84955DC304035 FOREIGN KEY (salle_id) REFERENCES salle (id)');
        $this->addSql('ALTER TABLE reservation_option_service ADD CONSTRAINT FK_EC5DCC9EB83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_option_service ADD CONSTRAINT FK_EC5DCC9E35B1D6 FOREIGN KEY (option_service_id) REFERENCES option_service (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE salle_equipement ADD CONSTRAINT FK_D338336BDC304035 FOREIGN KEY (salle_id) REFERENCES salle (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE salle_equipement ADD CONSTRAINT FK_D338336B806F0F5C FOREIGN KEY (equipement_id) REFERENCES equipement (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0A76ED395');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0DC304035');
        $this->addSql('ALTER TABLE disponibilite DROP FOREIGN KEY FK_2CBACE2FDC304035');
        $this->addSql('ALTER TABLE facture DROP FOREIGN KEY FK_FE866410B83297E7');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE paiement DROP FOREIGN KEY FK_B1DC7A1E7F2DEE08');
        $this->addSql('ALTER TABLE photo_salle DROP FOREIGN KEY FK_F4C679EBDC304035');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955A76ED395');
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C84955DC304035');
        $this->addSql('ALTER TABLE reservation_option_service DROP FOREIGN KEY FK_EC5DCC9EB83297E7');
        $this->addSql('ALTER TABLE reservation_option_service DROP FOREIGN KEY FK_EC5DCC9E35B1D6');
        $this->addSql('ALTER TABLE salle_equipement DROP FOREIGN KEY FK_D338336BDC304035');
        $this->addSql('ALTER TABLE salle_equipement DROP FOREIGN KEY FK_D338336B806F0F5C');
        $this->addSql('DROP TABLE avis');
        $this->addSql('DROP TABLE disponibilite');
        $this->addSql('DROP TABLE equipement');
        $this->addSql('DROP TABLE facture');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE option_service');
        $this->addSql('DROP TABLE paiement');
        $this->addSql('DROP TABLE photo_salle');
        $this->addSql('DROP TABLE reservation');
        $this->addSql('DROP TABLE reservation_option_service');
        $this->addSql('DROP TABLE salle');
        $this->addSql('DROP TABLE salle_equipement');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
