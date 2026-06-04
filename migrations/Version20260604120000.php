<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea ciclos de indicadores y relaciona semaforo_indicadores por FK para soportar historial.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ciclo_indicadores (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(20) NOT NULL, fecha_apertura DATE NOT NULL, fecha_cierre DATE NOT NULL, activo TINYINT(1) DEFAULT 0 NOT NULL, visible TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_CICLO_INDICADORES_NOMBRE (nombre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO ciclo_indicadores (nombre, fecha_apertura, fecha_cierre, activo, visible) VALUES ('2023-2024', '2023-07-01', '2024-06-30', 0, 1), ('2024-2025', '2024-07-01', '2025-06-30', 0, 1), ('2025-2026', '2025-07-01', '2026-06-30', 1, 1)");
        $this->addSql('ALTER TABLE semaforo_indicadores DROP INDEX UNIQ_SEMAFORO_INDICADOR_CICLO');
        $this->addSql('ALTER TABLE semaforo_indicadores ADD ciclo_indicadores_id INT DEFAULT NULL');
        $this->addSql('UPDATE semaforo_indicadores s INNER JOIN ciclo_indicadores c ON c.nombre = s.ciclo SET s.ciclo_indicadores_id = c.id');
        $this->addSql('ALTER TABLE semaforo_indicadores CHANGE ciclo_indicadores_id ciclo_indicadores_id INT NOT NULL');
        $this->addSql('ALTER TABLE semaforo_indicadores DROP ciclo');
        $this->addSql('CREATE INDEX IDX_10FE96AFC5CB60F7 ON semaforo_indicadores (ciclo_indicadores_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SEMAFORO_INDICADOR_CICLO ON semaforo_indicadores (id_indicadorbasico, ciclo_indicadores_id)');
        $this->addSql('ALTER TABLE semaforo_indicadores ADD CONSTRAINT FK_10FE96AF4C7C85D2 FOREIGN KEY (ciclo_indicadores_id) REFERENCES ciclo_indicadores (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE semaforo_indicadores DROP FOREIGN KEY FK_10FE96AF4C7C85D2');
        $this->addSql('ALTER TABLE semaforo_indicadores DROP INDEX UNIQ_SEMAFORO_INDICADOR_CICLO');
        $this->addSql('DROP INDEX IDX_10FE96AFC5CB60F7 ON semaforo_indicadores');
        $this->addSql('ALTER TABLE semaforo_indicadores ADD ciclo VARCHAR(20) DEFAULT NULL');
        $this->addSql('UPDATE semaforo_indicadores s INNER JOIN ciclo_indicadores c ON c.id = s.ciclo_indicadores_id SET s.ciclo = c.nombre');
        $this->addSql('ALTER TABLE semaforo_indicadores CHANGE ciclo ciclo VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE semaforo_indicadores DROP ciclo_indicadores_id');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SEMAFORO_INDICADOR_CICLO ON semaforo_indicadores (id_indicadorbasico, ciclo)');
        $this->addSql('DROP TABLE ciclo_indicadores');
    }
}
