<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Permite indicador_basico_id nulo en reporte_indicador_actividad (opcion No aplica)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reporte_indicador_actividad DROP FOREIGN KEY FK_REPORTE_IND_ACT_INDICADOR');
        $this->addSql('ALTER TABLE reporte_indicador_actividad CHANGE indicador_basico_id indicador_basico_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE reporte_indicador_actividad ADD CONSTRAINT FK_REPORTE_IND_ACT_INDICADOR FOREIGN KEY (indicador_basico_id) REFERENCES indicadores_basicos (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reporte_indicador_actividad DROP FOREIGN KEY FK_REPORTE_IND_ACT_INDICADOR');
        $this->addSql('ALTER TABLE reporte_indicador_actividad CHANGE indicador_basico_id indicador_basico_id INT NOT NULL');
        $this->addSql('ALTER TABLE reporte_indicador_actividad ADD CONSTRAINT FK_REPORTE_IND_ACT_INDICADOR FOREIGN KEY (indicador_basico_id) REFERENCES indicadores_basicos (id)');
    }
}
