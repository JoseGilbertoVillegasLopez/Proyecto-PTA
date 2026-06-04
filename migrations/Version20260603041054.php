<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260603041054 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE nombramiento (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, archivo VARCHAR(255) NOT NULL, nombre_original VARCHAR(255) DEFAULT NULL, tipo VARCHAR(100) NOT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, fecha_subida DATETIME NOT NULL, fecha_desactivacion DATETIME DEFAULT NULL, INDEX IDX_1405C4415D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE nombramiento ADD CONSTRAINT FK_1405C4415D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nombramiento DROP FOREIGN KEY FK_1405C4415D430949');
        $this->addSql('DROP TABLE nombramiento');
    }
}
