<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Registra módulo reportes_pta en modulo_sistema (con encargado y control de acceso)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO modulo_sistema (slug, label, descripcion, activo, usa_encargado, usa_acceso) VALUES ('reportes_pta', 'PTA', NULL, 1, 1, 1)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM modulo_sistema WHERE slug = 'reportes_pta'");
    }
}
