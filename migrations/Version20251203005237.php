<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251203005237 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE acciones (id INT AUTO_INCREMENT NOT NULL, encabezado_id INT NOT NULL, accion LONGTEXT NOT NULL, periodo JSON NOT NULL COMMENT \'(DC2Type:json)\', valor_alcanzado JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_29F5FFE7DD017133 (encabezado_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE encabezado (id INT AUTO_INCREMENT NOT NULL, responsable_id INT NOT NULL, objetivo LONGTEXT NOT NULL, nombre VARCHAR(255) NOT NULL, fecha_creacion DATE NOT NULL, fecha_concluido DATE DEFAULT NULL, tendencia TINYINT(1) DEFAULT NULL, status TINYINT(1) NOT NULL, INDEX IDX_B6A5789453C59D72 (responsable_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE indicadores (id INT AUTO_INCREMENT NOT NULL, encabezado_id INT NOT NULL, indicador VARCHAR(255) NOT NULL, formula VARCHAR(255) NOT NULL, valor NUMERIC(10, 2) NOT NULL, periodo VARCHAR(255) NOT NULL, INDEX IDX_B1E9F9AFDD017133 (encabezado_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE responsables (id INT AUTO_INCREMENT NOT NULL, encabezado_id INT NOT NULL, supervisor_id INT NOT NULL, aval_id INT NOT NULL, UNIQUE INDEX UNIQ_853808A5DD017133 (encabezado_id), INDEX IDX_853808A519E9AC5F (supervisor_id), INDEX IDX_853808A521747C97 (aval_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE acciones ADD CONSTRAINT FK_29F5FFE7DD017133 FOREIGN KEY (encabezado_id) REFERENCES encabezado (id)');
        $this->addSql('ALTER TABLE encabezado ADD CONSTRAINT FK_B6A5789453C59D72 FOREIGN KEY (responsable_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE indicadores ADD CONSTRAINT FK_B1E9F9AFDD017133 FOREIGN KEY (encabezado_id) REFERENCES encabezado (id)');
        $this->addSql('ALTER TABLE responsables ADD CONSTRAINT FK_853808A5DD017133 FOREIGN KEY (encabezado_id) REFERENCES encabezado (id)');
        $this->addSql('ALTER TABLE responsables ADD CONSTRAINT FK_853808A519E9AC5F FOREIGN KEY (supervisor_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE responsables ADD CONSTRAINT FK_853808A521747C97 FOREIGN KEY (aval_id) REFERENCES personal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE acciones DROP FOREIGN KEY FK_29F5FFE7DD017133');
        $this->addSql('ALTER TABLE encabezado DROP FOREIGN KEY FK_B6A5789453C59D72');
        $this->addSql('ALTER TABLE indicadores DROP FOREIGN KEY FK_B1E9F9AFDD017133');
        $this->addSql('ALTER TABLE responsables DROP FOREIGN KEY FK_853808A5DD017133');
        $this->addSql('ALTER TABLE responsables DROP FOREIGN KEY FK_853808A519E9AC5F');
        $this->addSql('ALTER TABLE responsables DROP FOREIGN KEY FK_853808A521747C97');
        $this->addSql('DROP TABLE acciones');
        $this->addSql('DROP TABLE encabezado');
        $this->addSql('DROP TABLE indicadores');
        $this->addSql('DROP TABLE responsables');
    }
}
