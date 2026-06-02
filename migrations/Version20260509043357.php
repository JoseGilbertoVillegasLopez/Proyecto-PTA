<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260509043357 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nombramiento ADD personal_id INT NOT NULL, ADD tipo VARCHAR(100) NOT NULL, ADD activo TINYINT(1) DEFAULT 1 NOT NULL, ADD fecha_subida DATE NOT NULL, ADD fecha_desactivacion DATE DEFAULT NULL, DROP fecha');
        $this->addSql('ALTER TABLE nombramiento ADD CONSTRAINT FK_1405C4415D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('CREATE INDEX IDX_1405C4415D430949 ON nombramiento (personal_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE nombramiento DROP FOREIGN KEY FK_1405C4415D430949');
        $this->addSql('DROP INDEX IDX_1405C4415D430949 ON nombramiento');
        $this->addSql('ALTER TABLE nombramiento ADD fecha DATETIME NOT NULL, DROP personal_id, DROP tipo, DROP activo, DROP fecha_subida, DROP fecha_desactivacion');
    }
}
