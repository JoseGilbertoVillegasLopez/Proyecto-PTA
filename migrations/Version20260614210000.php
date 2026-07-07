<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260614210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega usa_encargado a modulo_sistema; monitoreo no usa encargados';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE modulo_sistema ADD usa_encargado TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql("UPDATE modulo_sistema SET usa_encargado = 0 WHERE slug = 'monitoreo'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE modulo_sistema DROP COLUMN usa_encargado');
    }
}
