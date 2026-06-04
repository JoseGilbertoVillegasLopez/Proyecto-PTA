<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603033000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea tablas para semaforo de indicadores basicos y sus medias.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE semaforo_indicadores (id INT AUTO_INCREMENT NOT NULL, id_indicadorbasico INT NOT NULL, ciclo VARCHAR(20) NOT NULL, cantidad1 NUMERIC(14, 2) DEFAULT NULL, cantidad2 NUMERIC(14, 2) DEFAULT NULL, resultado_ciclo NUMERIC(14, 2) DEFAULT NULL, INDEX IDX_10FE96AFFEA9D90D (id_indicadorbasico), UNIQUE INDEX UNIQ_SEMAFORO_INDICADOR_CICLO (id_indicadorbasico, ciclo), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE semaforo_indicadores_media (id INT AUTO_INCREMENT NOT NULL, id_indicadorbasico INT NOT NULL, media_estatal NUMERIC(14, 2) DEFAULT NULL, media_nacional NUMERIC(14, 2) DEFAULT NULL, UNIQUE INDEX UNIQ_SEMAFORO_MEDIA_INDICADOR (id_indicadorbasico), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE semaforo_indicadores ADD CONSTRAINT FK_70DB97D75E98F03D FOREIGN KEY (id_indicadorbasico) REFERENCES indicadores_basicos (id)');
        $this->addSql('ALTER TABLE semaforo_indicadores_media ADD CONSTRAINT FK_939322FA5E98F03D FOREIGN KEY (id_indicadorbasico) REFERENCES indicadores_basicos (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE semaforo_indicadores DROP FOREIGN KEY FK_70DB97D75E98F03D');
        $this->addSql('ALTER TABLE semaforo_indicadores_media DROP FOREIGN KEY FK_939322FA5E98F03D');
        $this->addSql('DROP TABLE semaforo_indicadores');
        $this->addSql('DROP TABLE semaforo_indicadores_media');
    }
}
