<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260616100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Registra módulo personal en modulo_sistema (sin encargado, con control de acceso)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO modulo_sistema (slug, label, descripcion, activo, usa_encargado, usa_acceso) VALUES ('personal', 'Personal', NULL, 1, 0, 1)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM modulo_sistema WHERE slug = 'personal'");
    }
}
