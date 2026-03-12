<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260218153100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reporte_pta_evidencias (id INT AUTO_INCREMENT NOT NULL, reporte_pta_indicador_id INT NOT NULL, imagenes JSON NOT NULL COMMENT \'(DC2Type:json)\', descripcion LONGTEXT NOT NULL, INDEX IDX_4F70D592CEE01988 (reporte_pta_indicador_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reporte_pta_evidencias ADD CONSTRAINT FK_4F70D592CEE01988 FOREIGN KEY (reporte_pta_indicador_id) REFERENCES reporte_pta_indicador (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reporte_pta_evidencias DROP FOREIGN KEY FK_4F70D592CEE01988');
        $this->addSql('DROP TABLE reporte_pta_evidencias');
    }
}
