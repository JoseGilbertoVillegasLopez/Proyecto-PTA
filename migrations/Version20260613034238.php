<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260613034238 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE solicitud_gastos CHANGE transferencia_en_beneficio_de transferencia_en_beneficio_de VARCHAR(255) DEFAULT NULL, CHANGE cta_clave_beneficiario cta_clave_beneficiario VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE solicitud_gastos CHANGE transferencia_en_beneficio_de transferencia_en_beneficio_de VARCHAR(255) NOT NULL, CHANGE cta_clave_beneficiario cta_clave_beneficiario VARCHAR(255) NOT NULL');
    }
}
