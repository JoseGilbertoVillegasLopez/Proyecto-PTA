<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260704180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega usa_cargo_encargado a modulo_sistema y cargo a modulo_acceso; activa cargos en solicitud_gastos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE modulo_sistema ADD usa_cargo_encargado TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE modulo_acceso ADD cargo VARCHAR(20) DEFAULT NULL');
        $this->addSql("UPDATE modulo_sistema SET usa_cargo_encargado = 1 WHERE slug = 'solicitud_gastos'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE modulo_acceso DROP COLUMN cargo');
        $this->addSql('ALTER TABLE modulo_sistema DROP COLUMN usa_cargo_encargado');
    }
}
