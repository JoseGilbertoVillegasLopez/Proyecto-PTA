<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260603025139 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea tabla grupo_indicadores_basicos con 5 grupos predefinidos y agrega FK en indicadores_basicos';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE grupo_indicadores_basicos (id INT AUTO_INCREMENT NOT NULL, grupo VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO grupo_indicadores_basicos (grupo) VALUES ('ALUMNOS'), ('DOCENTES'), ('EXTENSIÓN Y VINCULACIÓN'), ('INVESTIGACIÓN'), ('ADMINISTRACIÓN')");
        $this->addSql('ALTER TABLE indicadores_basicos ADD grupo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE indicadores_basicos ADD CONSTRAINT FK_31665EF59C833003 FOREIGN KEY (grupo_id) REFERENCES grupo_indicadores_basicos (id)');
        $this->addSql('CREATE INDEX IDX_31665EF59C833003 ON indicadores_basicos (grupo_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE indicadores_basicos DROP FOREIGN KEY FK_31665EF59C833003');
        $this->addSql('DROP TABLE grupo_indicadores_basicos');
        $this->addSql('DROP INDEX IDX_31665EF59C833003 ON indicadores_basicos');
        $this->addSql('ALTER TABLE indicadores_basicos DROP grupo_id');
    }
}
