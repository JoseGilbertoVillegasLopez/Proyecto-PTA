<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260124202515 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE historial_acciones (id INT AUTO_INCREMENT NOT NULL, accion_id INT NOT NULL, mes INT NOT NULL, valor INT NOT NULL, fecha DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F1FB65403F4B5275 (accion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE historial_acciones_atrasos (id INT AUTO_INCREMENT NOT NULL, accion_id INT NOT NULL, mes INT NOT NULL, motivo LONGTEXT NOT NULL, fecha DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', valor INT NOT NULL, INDEX IDX_D312A5333F4B5275 (accion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE historial_acciones ADD CONSTRAINT FK_F1FB65403F4B5275 FOREIGN KEY (accion_id) REFERENCES acciones (id)');
        $this->addSql('ALTER TABLE historial_acciones_atrasos ADD CONSTRAINT FK_D312A5333F4B5275 FOREIGN KEY (accion_id) REFERENCES acciones (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE historial_acciones DROP FOREIGN KEY FK_F1FB65403F4B5275');
        $this->addSql('ALTER TABLE historial_acciones_atrasos DROP FOREIGN KEY FK_D312A5333F4B5275');
        $this->addSql('DROP TABLE historial_acciones');
        $this->addSql('DROP TABLE historial_acciones_atrasos');
    }
}
