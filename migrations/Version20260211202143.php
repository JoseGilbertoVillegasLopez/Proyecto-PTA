<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260211202143 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE partidas_presupuestales ADD activo TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE proceso_clave ADD activo TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE proceso_estrategico ADD activo TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE partidas_presupuestales DROP activo');
        $this->addSql('ALTER TABLE proceso_clave DROP activo');
        $this->addSql('ALTER TABLE proceso_estrategico DROP activo');
    }
}
