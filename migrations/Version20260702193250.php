<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260702193250 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega evidencias de gasto y documentos de verificación a solicitud_gastos';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE solicitud_gastos_evidencia (id INT AUTO_INCREMENT NOT NULL, solicitud_id INT NOT NULL, archivo_nombre_original VARCHAR(255) NOT NULL, archivo_nombre_guardado VARCHAR(255) NOT NULL, ruta VARCHAR(255) NOT NULL, mime_type VARCHAR(120) NOT NULL, extension VARCHAR(20) NOT NULL, tamano INT NOT NULL, orden INT NOT NULL, creado_fecha DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', INDEX IDX_B6F828E51CB9D6E4 (solicitud_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE solicitud_gastos_evidencia ADD CONSTRAINT FK_B6F828E51CB9D6E4 FOREIGN KEY (solicitud_id) REFERENCES solicitud_gastos (id)');
        $this->addSql('ALTER TABLE solicitud_gastos ADD documentos_verificacion JSON NOT NULL COMMENT \'(DC2Type:json)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE solicitud_gastos_evidencia DROP FOREIGN KEY FK_B6F828E51CB9D6E4');
        $this->addSql('DROP TABLE solicitud_gastos_evidencia');
        $this->addSql('ALTER TABLE solicitud_gastos DROP documentos_verificacion');
    }
}
