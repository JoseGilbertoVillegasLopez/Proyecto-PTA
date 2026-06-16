<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260615130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega usa_acceso a modulo_sistema; registra plantilla_indicadores solo con encargados';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE modulo_sistema ADD usa_acceso TINYINT(1) NOT NULL DEFAULT 1');
        $this->addSql("INSERT INTO modulo_sistema (slug, label, descripcion, activo, usa_encargado, usa_acceso) VALUES ('plantilla_indicadores', 'Semáforo de Indicadores', 'Captura de valores y medias de indicadores básicos por ciclo', 1, 1, 0)");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM modulo_sistema WHERE slug = 'plantilla_indicadores'");
        $this->addSql('ALTER TABLE modulo_sistema DROP COLUMN usa_acceso');
    }
}
