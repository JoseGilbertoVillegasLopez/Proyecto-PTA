<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Completa descripcion de los modulos reportes_pta y personal';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE modulo_sistema SET descripcion = 'Gestión de los proyectos PTA.' WHERE slug = 'reportes_pta'");
        $this->addSql("UPDATE modulo_sistema SET descripcion = 'Gestión del personal del sistema.' WHERE slug = 'personal'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE modulo_sistema SET descripcion = NULL WHERE slug = 'reportes_pta'");
        $this->addSql("UPDATE modulo_sistema SET descripcion = NULL WHERE slug = 'personal'");
    }
}
