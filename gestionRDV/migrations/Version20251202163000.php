<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create indisponibilite table for medecin unavailabilities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE indisponibilite (id INT AUTO_INCREMENT NOT NULL, medecin_id INT NOT NULL, debut DATETIME NOT NULL, fin DATETIME NOT NULL, motif VARCHAR(255) DEFAULT NULL, INDEX IDX_8B1BF8F54F31A84 (medecin_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE indisponibilite ADD CONSTRAINT FK_8B1BF8F54F31A84 FOREIGN KEY (medecin_id) REFERENCES medecin (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE indisponibilite DROP FOREIGN KEY FK_8B1BF8F54F31A84');
        $this->addSql('DROP TABLE indisponibilite');
    }
}
