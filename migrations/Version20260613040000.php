<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed tipos de solicitud';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO tipo_solicitud (nombre) VALUES
            ('Viáticos a comprobar'),
            ('Reposición de gastos erogados'),
            ('Pago directo'),
            ('Gastos por comprobar'),
            ('Capítulo 1000 serv. Personales')
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM tipo_solicitud");
    }
}
