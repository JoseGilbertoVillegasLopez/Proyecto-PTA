<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260615120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Registra modulo reporte_indicadores en modulo_sistema con usa_encargado=1';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO modulo_sistema (slug, label, descripcion, activo, usa_encargado) VALUES ('reporte_indicadores', 'Reportes de Indicadores', 'Captura y revisión de reportes trimestrales de indicadores básicos', 1, 1)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM modulo_sistema WHERE slug = 'reporte_indicadores'");
    }
}
