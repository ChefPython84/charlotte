<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251029181720 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_contrat ADD commentaire_mairie LONGTEXT DEFAULT NULL, ADD validation_mairie TINYINT(1) DEFAULT 0 NOT NULL, CHANGE details_manifestation details_manifestation LONGTEXT DEFAULT NULL, CHANGE besoins_techniques besoins_techniques LONGTEXT DEFAULT NULL, CHANGE assurance_path assurance_path VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE dossier_contrat DROP commentaire_mairie, DROP validation_mairie, CHANGE details_manifestation details_manifestation LONGTEXT NOT NULL, CHANGE besoins_techniques besoins_techniques LONGTEXT NOT NULL, CHANGE assurance_path assurance_path VARCHAR(255) NOT NULL');
    }
}
