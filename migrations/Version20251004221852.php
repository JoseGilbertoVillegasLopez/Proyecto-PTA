<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251004221852 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE departamento (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personal (id INT AUTO_INCREMENT NOT NULL, puesto_id INT NOT NULL, departamento_id INT NOT NULL, nombre VARCHAR(255) NOT NULL, ap_paterno VARCHAR(255) NOT NULL, ap_materno VARCHAR(255) NOT NULL, correo VARCHAR(255) NOT NULL, activo TINYINT(1) NOT NULL, INDEX IDX_F18A6D845035E9DA (puesto_id), INDEX IDX_F18A6D845A91C08D (departamento_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE puesto (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE usuario (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, usuario VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, rol VARCHAR(255) NOT NULL, activo TINYINT(1) NOT NULL, INDEX IDX_2265B05D5D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE personal ADD CONSTRAINT FK_F18A6D845035E9DA FOREIGN KEY (puesto_id) REFERENCES puesto (id)');
        $this->addSql('ALTER TABLE personal ADD CONSTRAINT FK_F18A6D845A91C08D FOREIGN KEY (departamento_id) REFERENCES departamento (id)');
        $this->addSql('ALTER TABLE usuario ADD CONSTRAINT FK_2265B05D5D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE personal DROP FOREIGN KEY FK_F18A6D845035E9DA');
        $this->addSql('ALTER TABLE personal DROP FOREIGN KEY FK_F18A6D845A91C08D');
        $this->addSql('ALTER TABLE usuario DROP FOREIGN KEY FK_2265B05D5D430949');
        $this->addSql('DROP TABLE departamento');
        $this->addSql('DROP TABLE personal');
        $this->addSql('DROP TABLE puesto');
        $this->addSql('DROP TABLE usuario');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
