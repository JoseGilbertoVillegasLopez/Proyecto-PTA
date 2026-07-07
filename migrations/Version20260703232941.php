<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260703232941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Reemplaza el checklist documentos_verificacion por un select unico + descripcion, y agrega jefe_area/autoriza a solicitud_gastos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE solicitud_gastos ADD jefe_area_id INT DEFAULT NULL, ADD autoriza_id INT DEFAULT NULL, ADD documento_verificacion VARCHAR(60) DEFAULT NULL, ADD documento_verificacion_descripcion VARCHAR(255) DEFAULT NULL');
        $this->addSql("UPDATE solicitud_gastos SET documento_verificacion = JSON_UNQUOTE(JSON_EXTRACT(documentos_verificacion, '$[0]')) WHERE JSON_LENGTH(documentos_verificacion) > 0");
        $this->addSql('ALTER TABLE solicitud_gastos DROP documentos_verificacion');
        $this->addSql('ALTER TABLE solicitud_gastos ADD CONSTRAINT FK_23AA7F5FFE5BB628 FOREIGN KEY (jefe_area_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE solicitud_gastos ADD CONSTRAINT FK_23AA7F5FB3CD7DBC FOREIGN KEY (autoriza_id) REFERENCES personal (id)');
        $this->addSql('CREATE INDEX IDX_23AA7F5FFE5BB628 ON solicitud_gastos (jefe_area_id)');
        $this->addSql('CREATE INDEX IDX_23AA7F5FB3CD7DBC ON solicitud_gastos (autoriza_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE solicitud_gastos DROP FOREIGN KEY FK_23AA7F5FFE5BB628');
        $this->addSql('ALTER TABLE solicitud_gastos DROP FOREIGN KEY FK_23AA7F5FB3CD7DBC');
        $this->addSql('DROP INDEX IDX_23AA7F5FFE5BB628 ON solicitud_gastos');
        $this->addSql('DROP INDEX IDX_23AA7F5FB3CD7DBC ON solicitud_gastos');
        $this->addSql('ALTER TABLE solicitud_gastos ADD documentos_verificacion JSON DEFAULT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql("UPDATE solicitud_gastos SET documentos_verificacion = JSON_ARRAY(documento_verificacion) WHERE documento_verificacion IS NOT NULL");
        $this->addSql("UPDATE solicitud_gastos SET documentos_verificacion = JSON_ARRAY() WHERE documento_verificacion IS NULL");
        $this->addSql('ALTER TABLE solicitud_gastos MODIFY documentos_verificacion JSON NOT NULL COMMENT \'(DC2Type:json)\'');
        $this->addSql('ALTER TABLE solicitud_gastos DROP jefe_area_id, DROP autoriza_id, DROP documento_verificacion, DROP documento_verificacion_descripcion');
    }
}
