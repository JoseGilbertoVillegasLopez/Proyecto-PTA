<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260517010706 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reporte_pta_accion CHANGE proceso_estrategico_id proceso_estrategico_id INT DEFAULT NULL, CHANGE proceso_clave_id proceso_clave_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reporte_pta_accion_partida CHANGE partida_presupuestal_id partida_presupuestal_id INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reporte_pta_accion CHANGE proceso_estrategico_id proceso_estrategico_id INT NOT NULL, CHANGE proceso_clave_id proceso_clave_id INT NOT NULL');
        $this->addSql('ALTER TABLE reporte_pta_accion_partida CHANGE partida_presupuestal_id partida_presupuestal_id INT NOT NULL');
    }
}
