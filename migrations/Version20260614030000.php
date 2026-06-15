<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260614030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea actividades y evidencias para reportes trimestrales de indicadores.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reporte_indicador_actividad (id INT AUTO_INCREMENT NOT NULL, reporte_trimestre_id INT NOT NULL, indicador_basico_id INT NOT NULL, accion LONGTEXT NOT NULL, descripcion LONGTEXT NOT NULL, INDEX IDX_REPORTE_IND_ACT_TRIMESTRE (reporte_trimestre_id), INDEX IDX_REPORTE_IND_ACT_INDICADOR (indicador_basico_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporte_indicador_evidencia (id INT AUTO_INCREMENT NOT NULL, actividad_id INT NOT NULL, archivo_nombre_original VARCHAR(255) NOT NULL, archivo_nombre_guardado VARCHAR(255) NOT NULL, ruta VARCHAR(255) NOT NULL, mime_type VARCHAR(120) NOT NULL, extension VARCHAR(20) NOT NULL, tamano INT NOT NULL, orden INT NOT NULL, creado_fecha DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', INDEX IDX_REPORTE_IND_EVID_ACTIVIDAD (actividad_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reporte_indicador_actividad ADD CONSTRAINT FK_REPORTE_IND_ACT_TRIMESTRE FOREIGN KEY (reporte_trimestre_id) REFERENCES reporte_indicador_trimestre (id)');
        $this->addSql('ALTER TABLE reporte_indicador_actividad ADD CONSTRAINT FK_REPORTE_IND_ACT_INDICADOR FOREIGN KEY (indicador_basico_id) REFERENCES indicadores_basicos (id)');
        $this->addSql('ALTER TABLE reporte_indicador_evidencia ADD CONSTRAINT FK_REPORTE_IND_EVID_ACTIVIDAD FOREIGN KEY (actividad_id) REFERENCES reporte_indicador_actividad (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reporte_indicador_evidencia DROP FOREIGN KEY FK_REPORTE_IND_EVID_ACTIVIDAD');
        $this->addSql('ALTER TABLE reporte_indicador_actividad DROP FOREIGN KEY FK_REPORTE_IND_ACT_TRIMESTRE');
        $this->addSql('ALTER TABLE reporte_indicador_actividad DROP FOREIGN KEY FK_REPORTE_IND_ACT_INDICADOR');
        $this->addSql('DROP TABLE reporte_indicador_evidencia');
        $this->addSql('DROP TABLE reporte_indicador_actividad');
    }
}
