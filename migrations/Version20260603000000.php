<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change nombramiento upload and deactivation dates back to date-only columns.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE nombramiento CHANGE fecha_subida fecha_subida DATE NOT NULL, CHANGE fecha_desactivacion fecha_desactivacion DATE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE nombramiento CHANGE fecha_subida fecha_subida DATETIME NOT NULL, CHANGE fecha_desactivacion fecha_desactivacion DATETIME DEFAULT NULL');
    }
}
