<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251123022433 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, usuario VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, cambiar_password TINYINT(1) DEFAULT 1 NOT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_8D93D6495D430949 (personal_id), UNIQUE INDEX UNIQ_IDENTIFIER_USUARIO (usuario), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6495D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE usuario DROP FOREIGN KEY FK_2265B05D5D430949');
        $this->addSql('DROP TABLE usuario');
        $this->addSql('ALTER TABLE personal CHANGE activo activo TINYINT(1) DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE usuario (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, usuario VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, rol VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, activo TINYINT(1) NOT NULL, INDEX IDX_2265B05D5D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE usuario ADD CONSTRAINT FK_2265B05D5D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6495D430949');
        $this->addSql('DROP TABLE user');
        $this->addSql('ALTER TABLE personal CHANGE activo activo TINYINT(1) NOT NULL');
    }
}
