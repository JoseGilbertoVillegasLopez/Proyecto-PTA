<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260613161652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE solicitud_gastos_bancos (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, estado VARCHAR(20) DEFAULT \'activo\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE solicitud_gastos ADD banco_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE solicitud_gastos ADD CONSTRAINT FK_23AA7F5FCC04A73E FOREIGN KEY (banco_id) REFERENCES solicitud_gastos_bancos (id)');
        $this->addSql('CREATE INDEX IDX_23AA7F5FCC04A73E ON solicitud_gastos (banco_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE solicitud_gastos DROP FOREIGN KEY FK_23AA7F5FCC04A73E');
        $this->addSql('DROP TABLE solicitud_gastos_bancos');
        $this->addSql('DROP INDEX IDX_23AA7F5FCC04A73E ON solicitud_gastos');
        $this->addSql('ALTER TABLE solicitud_gastos DROP banco_id');
    }
}
