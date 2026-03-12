<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260215231030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reporte_pta_accion (id INT AUTO_INCREMENT NOT NULL, reporte_indicador_id INT NOT NULL, proceso_estrategico_id INT NOT NULL, proceso_clave_id INT NOT NULL, accion LONGTEXT NOT NULL, INDEX IDX_66DD814EEDB33A27 (reporte_indicador_id), INDEX IDX_66DD814E8DFC750A (proceso_estrategico_id), INDEX IDX_66DD814E9CF1B3C4 (proceso_clave_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporte_pta_accion_partida (id INT AUTO_INCREMENT NOT NULL, reporte_accion_id INT NOT NULL, partida_presupuestal_id INT NOT NULL, cantidad NUMERIC(12, 2) NOT NULL, INDEX IDX_772D05F4AB50CCCB (reporte_accion_id), INDEX IDX_772D05F490B9C97D (partida_presupuestal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporte_pta_indicador (id INT AUTO_INCREMENT NOT NULL, reporte_trimestre_id INT NOT NULL, indicador_basico_id INT NOT NULL, indicador_pta_id INT NOT NULL, responsable_puesto_id INT NOT NULL, unidad_medida VARCHAR(255) NOT NULL, meta NUMERIC(10, 2) NOT NULL, resultado NUMERIC(10, 2) NOT NULL, porcentaje_avance NUMERIC(5, 2) NOT NULL, formula LONGTEXT NOT NULL, medio_verificacion LONGTEXT NOT NULL, meta_cumplida LONGTEXT NOT NULL, INDEX IDX_34E60E4913BCE06B (reporte_trimestre_id), INDEX IDX_34E60E49F5C2B885 (indicador_basico_id), INDEX IDX_34E60E498999058 (indicador_pta_id), INDEX IDX_34E60E49AD73B8CC (responsable_puesto_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporte_pta_trimestre (id INT AUTO_INCREMENT NOT NULL, encabezado_id INT NOT NULL, anio INT NOT NULL, trimestre INT NOT NULL, estado TINYINT(1) DEFAULT 0 NOT NULL, creado_fecha DATETIME NOT NULL, entregado_fecha DATETIME DEFAULT NULL, INDEX IDX_ADF28CC2DD017133 (encabezado_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reporte_pta_accion ADD CONSTRAINT FK_66DD814EEDB33A27 FOREIGN KEY (reporte_indicador_id) REFERENCES reporte_pta_indicador (id)');
        $this->addSql('ALTER TABLE reporte_pta_accion ADD CONSTRAINT FK_66DD814E8DFC750A FOREIGN KEY (proceso_estrategico_id) REFERENCES proceso_estrategico (id)');
        $this->addSql('ALTER TABLE reporte_pta_accion ADD CONSTRAINT FK_66DD814E9CF1B3C4 FOREIGN KEY (proceso_clave_id) REFERENCES proceso_clave (id)');
        $this->addSql('ALTER TABLE reporte_pta_accion_partida ADD CONSTRAINT FK_772D05F4AB50CCCB FOREIGN KEY (reporte_accion_id) REFERENCES reporte_pta_accion (id)');
        $this->addSql('ALTER TABLE reporte_pta_accion_partida ADD CONSTRAINT FK_772D05F490B9C97D FOREIGN KEY (partida_presupuestal_id) REFERENCES partidas_presupuestales (id)');
        $this->addSql('ALTER TABLE reporte_pta_indicador ADD CONSTRAINT FK_34E60E4913BCE06B FOREIGN KEY (reporte_trimestre_id) REFERENCES reporte_pta_trimestre (id)');
        $this->addSql('ALTER TABLE reporte_pta_indicador ADD CONSTRAINT FK_34E60E49F5C2B885 FOREIGN KEY (indicador_basico_id) REFERENCES indicadores_basicos (id)');
        $this->addSql('ALTER TABLE reporte_pta_indicador ADD CONSTRAINT FK_34E60E498999058 FOREIGN KEY (indicador_pta_id) REFERENCES indicadores (id)');
        $this->addSql('ALTER TABLE reporte_pta_indicador ADD CONSTRAINT FK_34E60E49AD73B8CC FOREIGN KEY (responsable_puesto_id) REFERENCES puesto (id)');
        $this->addSql('ALTER TABLE reporte_pta_trimestre ADD CONSTRAINT FK_ADF28CC2DD017133 FOREIGN KEY (encabezado_id) REFERENCES encabezado (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reporte_pta_accion DROP FOREIGN KEY FK_66DD814EEDB33A27');
        $this->addSql('ALTER TABLE reporte_pta_accion DROP FOREIGN KEY FK_66DD814E8DFC750A');
        $this->addSql('ALTER TABLE reporte_pta_accion DROP FOREIGN KEY FK_66DD814E9CF1B3C4');
        $this->addSql('ALTER TABLE reporte_pta_accion_partida DROP FOREIGN KEY FK_772D05F4AB50CCCB');
        $this->addSql('ALTER TABLE reporte_pta_accion_partida DROP FOREIGN KEY FK_772D05F490B9C97D');
        $this->addSql('ALTER TABLE reporte_pta_indicador DROP FOREIGN KEY FK_34E60E4913BCE06B');
        $this->addSql('ALTER TABLE reporte_pta_indicador DROP FOREIGN KEY FK_34E60E49F5C2B885');
        $this->addSql('ALTER TABLE reporte_pta_indicador DROP FOREIGN KEY FK_34E60E498999058');
        $this->addSql('ALTER TABLE reporte_pta_indicador DROP FOREIGN KEY FK_34E60E49AD73B8CC');
        $this->addSql('ALTER TABLE reporte_pta_trimestre DROP FOREIGN KEY FK_ADF28CC2DD017133');
        $this->addSql('DROP TABLE reporte_pta_accion');
        $this->addSql('DROP TABLE reporte_pta_accion_partida');
        $this->addSql('DROP TABLE reporte_pta_indicador');
        $this->addSql('DROP TABLE reporte_pta_trimestre');
    }
}
