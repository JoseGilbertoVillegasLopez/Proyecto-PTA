<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260111205137 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE puesto ADD supervisor_directo_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE puesto ADD CONSTRAINT FK_47C3D2DE7EA96164 FOREIGN KEY (supervisor_directo_id) REFERENCES puesto (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_47C3D2DE7EA96164 ON puesto (supervisor_directo_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE puesto DROP FOREIGN KEY FK_47C3D2DE7EA96164');
        $this->addSql('DROP INDEX IDX_47C3D2DE7EA96164 ON puesto');
        $this->addSql('ALTER TABLE puesto DROP supervisor_directo_id');
    }
}
