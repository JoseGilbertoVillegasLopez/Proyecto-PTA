<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea tabla de reportes de indicadores por usuario.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE reporte_indicadores (id INT AUTO_INCREMENT NOT NULL, creado_por_id INT NOT NULL, titulo VARCHAR(160) NOT NULL, estado VARCHAR(40) DEFAULT \'Borrador\' NOT NULL, creado_fecha DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', actualizado_fecha DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_REPORTE_INDICADORES_CREADO_POR (creado_por_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reporte_indicadores ADD CONSTRAINT FK_REPORTE_INDICADORES_CREADO_POR FOREIGN KEY (creado_por_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE reporte_indicadores DROP FOREIGN KEY FK_REPORTE_INDICADORES_CREADO_POR');
        $this->addSql('DROP TABLE reporte_indicadores');
    }
}
