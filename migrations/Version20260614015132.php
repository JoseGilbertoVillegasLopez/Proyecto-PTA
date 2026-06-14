<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260614015132 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea tablas modulo_sistema y modulo_acceso, e inserta módulos iniciales';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE modulo_acceso (id INT AUTO_INCREMENT NOT NULL, modulo_id INT NOT NULL, puesto_id INT NOT NULL, tipo VARCHAR(20) NOT NULL, INDEX IDX_29AB2BFCC07F55F5 (modulo_id), INDEX IDX_29AB2BFC5035E9DA (puesto_id), UNIQUE INDEX uniq_modulo_puesto (modulo_id, puesto_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE modulo_sistema (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(100) NOT NULL, label VARCHAR(255) NOT NULL, descripcion LONGTEXT DEFAULT NULL, activo TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_AF971FE6989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE modulo_acceso ADD CONSTRAINT FK_29AB2BFCC07F55F5 FOREIGN KEY (modulo_id) REFERENCES modulo_sistema (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE modulo_acceso ADD CONSTRAINT FK_29AB2BFC5035E9DA FOREIGN KEY (puesto_id) REFERENCES puesto (id) ON DELETE CASCADE');

        // Seeding: módulos iniciales
        $this->addSql("INSERT INTO modulo_sistema (slug, label, descripcion, activo) VALUES
            ('solicitud_gastos', 'Solicitud de Gastos', 'Gestión y revisión de solicitudes de viáticos', 1),
            ('monitoreo', 'Monitoreo PTA', 'Visualización del monitoreo general de PTAs', 1)
        ");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE modulo_acceso DROP FOREIGN KEY FK_29AB2BFCC07F55F5');
        $this->addSql('ALTER TABLE modulo_acceso DROP FOREIGN KEY FK_29AB2BFC5035E9DA');
        $this->addSql('DROP TABLE modulo_acceso');
        $this->addSql('DROP TABLE modulo_sistema');
    }
}
