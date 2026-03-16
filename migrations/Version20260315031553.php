<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260315031553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE departamento_indicadores_basicos (departamento_id INT NOT NULL, indicadores_basicos_id INT NOT NULL, INDEX IDX_E7BEA87E5A91C08D (departamento_id), INDEX IDX_E7BEA87E55A7C08D (indicadores_basicos_id), PRIMARY KEY(departamento_id, indicadores_basicos_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos ADD CONSTRAINT FK_E7BEA87E5A91C08D FOREIGN KEY (departamento_id) REFERENCES departamento (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos ADD CONSTRAINT FK_E7BEA87E55A7C08D FOREIGN KEY (indicadores_basicos_id) REFERENCES indicadores_basicos (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departamento_indicadores_basicos DROP FOREIGN KEY FK_E7BEA87E5A91C08D');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos DROP FOREIGN KEY FK_E7BEA87E55A7C08D');
        $this->addSql('DROP TABLE departamento_indicadores_basicos');
    }
}
