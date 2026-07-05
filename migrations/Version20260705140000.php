<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260705140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega serie (abreviacion) a puesto, usada como Serie en solicitud de gastos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puesto ADD serie VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE puesto DROP COLUMN serie');
    }
}
