<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260601204316 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Indicadores: agrega capturaEnPorcentaje para definir la unidad de captura mensual cuando esPorcentaje=true';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE indicadores ADD captura_en_porcentaje TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE indicadores DROP captura_en_porcentaje');
    }
}
