<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260530231400 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rediseño seguimiento PTA: mesesCumplidos en acciones, valorMensual en indicadores, motivo en historial_acciones, nueva tabla historial_indicador_valor, elimina historial_acciones_atrasos';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE historial_indicador_valor (id INT AUTO_INCREMENT NOT NULL, indicador_id INT NOT NULL, mes INT NOT NULL, valor NUMERIC(10, 2) NOT NULL, motivo LONGTEXT DEFAULT NULL, fecha DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2C9ACC9C47D487D1 (indicador_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE historial_indicador_valor ADD CONSTRAINT FK_2C9ACC9C47D487D1 FOREIGN KEY (indicador_id) REFERENCES indicadores (id)');
        $this->addSql('ALTER TABLE historial_acciones_atrasos DROP FOREIGN KEY FK_D312A5333F4B5275');
        $this->addSql('DROP TABLE historial_acciones_atrasos');
        $this->addSql('ALTER TABLE acciones CHANGE valor_alcanzado meses_cumplidos JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE historial_acciones ADD motivo LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE indicadores ADD valor_mensual JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE historial_acciones_atrasos (id INT AUTO_INCREMENT NOT NULL, accion_id INT NOT NULL, mes INT NOT NULL, motivo LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, fecha DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', valor INT NOT NULL, INDEX IDX_D312A5333F4B5275 (accion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE historial_acciones_atrasos ADD CONSTRAINT FK_D312A5333F4B5275 FOREIGN KEY (accion_id) REFERENCES acciones (id)');
        $this->addSql('ALTER TABLE historial_indicador_valor DROP FOREIGN KEY FK_2C9ACC9C47D487D1');
        $this->addSql('DROP TABLE historial_indicador_valor');
        $this->addSql('ALTER TABLE acciones CHANGE meses_cumplidos valor_alcanzado JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE historial_acciones DROP motivo');
        $this->addSql('ALTER TABLE indicadores DROP valor_mensual');
    }
}
