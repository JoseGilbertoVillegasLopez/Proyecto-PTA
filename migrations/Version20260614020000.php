<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260614020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cambia fechas de reporte indicador trimestral de datetime a date.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reporte_indicador_trimestre CHANGE creado_fecha creado_fecha DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', CHANGE entregado_fecha entregado_fecha DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reporte_indicador_trimestre CHANGE creado_fecha creado_fecha DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE entregado_fecha entregado_fecha DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
