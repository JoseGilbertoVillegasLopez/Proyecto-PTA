<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260614200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permite que un puesto sea encargado y con acceso al mismo tiempo en un módulo';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE modulo_acceso DROP INDEX uniq_modulo_puesto');
        $this->addSql('CREATE UNIQUE INDEX uniq_modulo_puesto_tipo ON modulo_acceso (modulo_id, puesto_id, tipo)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE modulo_acceso DROP INDEX uniq_modulo_puesto_tipo');
        $this->addSql('CREATE UNIQUE INDEX uniq_modulo_puesto ON modulo_acceso (modulo_id, puesto_id)');
    }
}
