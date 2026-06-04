<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260604153852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE ciclo_indicadores (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(20) NOT NULL, fecha_apertura DATE NOT NULL, fecha_cierre DATE NOT NULL, activo TINYINT(1) DEFAULT 0 NOT NULL, visible TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_CICLO_INDICADORES_NOMBRE (nombre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP INDEX UNIQ_SEMAFORO_INDICADOR_CICLO ON semaforo_indicadores');
        $this->addSql('ALTER TABLE semaforo_indicadores ADD ciclo_indicadores_id INT NOT NULL, DROP ciclo');
        $this->addSql('ALTER TABLE semaforo_indicadores ADD CONSTRAINT FK_10FE96AFC5CB60F7 FOREIGN KEY (ciclo_indicadores_id) REFERENCES ciclo_indicadores (id)');
        $this->addSql('CREATE INDEX IDX_10FE96AFC5CB60F7 ON semaforo_indicadores (ciclo_indicadores_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SEMAFORO_INDICADOR_CICLO ON semaforo_indicadores (id_indicadorbasico, ciclo_indicadores_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE semaforo_indicadores DROP FOREIGN KEY FK_10FE96AFC5CB60F7');
        $this->addSql('DROP TABLE ciclo_indicadores');
        $this->addSql('DROP INDEX IDX_10FE96AFC5CB60F7 ON semaforo_indicadores');
        $this->addSql('DROP INDEX UNIQ_SEMAFORO_INDICADOR_CICLO ON semaforo_indicadores');
        $this->addSql('ALTER TABLE semaforo_indicadores ADD ciclo VARCHAR(20) NOT NULL, DROP ciclo_indicadores_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SEMAFORO_INDICADOR_CICLO ON semaforo_indicadores (id_indicadorbasico, ciclo)');
    }
}
