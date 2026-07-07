<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260704181500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crea solicitud_gastos_revision y solicitud_gastos_comprobante; migra solicitudes existentes al nuevo catálogo de estados';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE solicitud_gastos_revision (id INT AUTO_INCREMENT NOT NULL, solicitud_id INT NOT NULL, personal_id INT DEFAULT NULL, cargo VARCHAR(20) NOT NULL, estado VARCHAR(20) NOT NULL DEFAULT \'pendiente\', comentario LONGTEXT DEFAULT NULL, fecha_apertura DATETIME DEFAULT NULL, fecha_resolucion DATETIME DEFAULT NULL, UNIQUE INDEX uniq_solicitud_cargo (solicitud_id, cargo), INDEX IDX_F9DBA68F1CB9D6E4 (solicitud_id), INDEX IDX_F9DBA68F5D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE solicitud_gastos_revision ADD CONSTRAINT FK_sgr_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitud_gastos (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE solicitud_gastos_revision ADD CONSTRAINT FK_sgr_personal FOREIGN KEY (personal_id) REFERENCES personal (id)');

        $this->addSql('CREATE TABLE solicitud_gastos_comprobante (id INT AUTO_INCREMENT NOT NULL, solicitud_id INT NOT NULL, archivo_nombre_original VARCHAR(255) NOT NULL, archivo_nombre_guardado VARCHAR(255) NOT NULL, ruta VARCHAR(255) NOT NULL, mime_type VARCHAR(120) NOT NULL, extension VARCHAR(20) NOT NULL, tamano INT NOT NULL, creado_fecha DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', UNIQUE INDEX UNIQ_2D501B4A1CB9D6E4 (solicitud_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE solicitud_gastos_comprobante ADD CONSTRAINT FK_sgc_solicitud FOREIGN KEY (solicitud_id) REFERENCES solicitud_gastos (id) ON DELETE CASCADE');

        // Las solicitudes que ya estaban "revisada" bajo el flujo binario anterior pasan a "aceptada";
        // no tienen historial real de voto porque el flujo de 3 revisores no existía.
        $this->addSql("UPDATE solicitud_gastos SET estado = 'aceptada' WHERE estado = 'revisada'");

        $this->addSql("INSERT INTO solicitud_gastos_revision (solicitud_id, cargo, estado) SELECT id, 'revisor', 'pendiente' FROM solicitud_gastos");
        $this->addSql("INSERT INTO solicitud_gastos_revision (solicitud_id, cargo, estado) SELECT id, 'supervisor', 'pendiente' FROM solicitud_gastos");
        $this->addSql("INSERT INTO solicitud_gastos_revision (solicitud_id, cargo, estado) SELECT id, 'autoriza', 'pendiente' FROM solicitud_gastos");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE solicitud_gastos_comprobante DROP FOREIGN KEY FK_sgc_solicitud');
        $this->addSql('DROP TABLE solicitud_gastos_comprobante');
        $this->addSql('ALTER TABLE solicitud_gastos_revision DROP FOREIGN KEY FK_sgr_solicitud');
        $this->addSql('ALTER TABLE solicitud_gastos_revision DROP FOREIGN KEY FK_sgr_personal');
        $this->addSql('DROP TABLE solicitud_gastos_revision');
        // No se revierte el estado 'aceptada' -> 'revisada': no hay forma de distinguir esas filas
        // de solicitudes que llegaron a 'aceptada' por el nuevo flujo de votos tras la migración.
    }
}
