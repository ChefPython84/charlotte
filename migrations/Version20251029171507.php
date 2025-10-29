<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251029171507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE dossier_contrat (id INT AUTO_INCREMENT NOT NULL, details_manifestation LONGTEXT NOT NULL, besoins_techniques LONGTEXT NOT NULL, plan_securite_path VARCHAR(255) DEFAULT NULL, assurance_path VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reservation ADD dossier_contrat_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT FK_42C849553659423A FOREIGN KEY (dossier_contrat_id) REFERENCES dossier_contrat (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_42C849553659423A ON reservation (dossier_contrat_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation DROP FOREIGN KEY FK_42C849553659423A');
        $this->addSql('DROP TABLE dossier_contrat');
        $this->addSql('DROP INDEX UNIQ_42C849553659423A ON reservation');
        $this->addSql('ALTER TABLE reservation DROP dossier_contrat_id');
    }
}
