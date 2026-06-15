<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260614010000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea reportes trimestrales de indicadores por personal.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reporte_indicador_trimestre (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, departamento_id INT NOT NULL, puesto_id INT NOT NULL, anio INT NOT NULL, trimestre INT NOT NULL, estado VARCHAR(20) DEFAULT \'borrador\' NOT NULL, creado_fecha DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', entregado_fecha DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_REPORTE_INDICADOR_PERSONAL (personal_id), INDEX IDX_REPORTE_INDICADOR_DEPARTAMENTO (departamento_id), INDEX IDX_REPORTE_INDICADOR_PUESTO (puesto_id), UNIQUE INDEX UNIQ_REPORTE_INDICADOR_PERSONAL_ANIO_TRIMESTRE (personal_id, anio, trimestre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre ADD CONSTRAINT FK_REPORTE_INDICADOR_PERSONAL FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre ADD CONSTRAINT FK_REPORTE_INDICADOR_DEPARTAMENTO FOREIGN KEY (departamento_id) REFERENCES departamento (id)');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre ADD CONSTRAINT FK_REPORTE_INDICADOR_PUESTO FOREIGN KEY (puesto_id) REFERENCES puesto (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reporte_indicador_trimestre DROP FOREIGN KEY FK_REPORTE_INDICADOR_PERSONAL');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre DROP FOREIGN KEY FK_REPORTE_INDICADOR_DEPARTAMENTO');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre DROP FOREIGN KEY FK_REPORTE_INDICADOR_PUESTO');
        $this->addSql('DROP TABLE reporte_indicador_trimestre');
    }
}
