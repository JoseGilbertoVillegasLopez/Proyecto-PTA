<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Configuracion de solicitud_gastos (criterio de aprobacion, motivo de rechazo, folio) + columna folio';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE solicitud_gastos_configuracion (id INT NOT NULL, criterio_aprobacion VARCHAR(20) DEFAULT \'unanime\' NOT NULL, mostrar_motivo_rechazo TINYINT(1) DEFAULT 0 NOT NULL, folio_aplica_a VARCHAR(30) DEFAULT \'solo_aceptadas\' NOT NULL, folio_ciclo_reinicio VARCHAR(20) DEFAULT \'continuo\' NOT NULL, folio_contador_actual INT DEFAULT 0 NOT NULL, folio_periodo_actual VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO solicitud_gastos_configuracion (id, criterio_aprobacion, mostrar_motivo_rechazo, folio_aplica_a, folio_ciclo_reinicio, folio_contador_actual, folio_periodo_actual) VALUES (1, \'unanime\', 0, \'solo_aceptadas\', \'continuo\', 0, NULL)');
        $this->addSql('ALTER TABLE solicitud_gastos ADD folio INT DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SOLICITUD_GASTOS_FOLIO ON solicitud_gastos (folio)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_SOLICITUD_GASTOS_FOLIO ON solicitud_gastos');
        $this->addSql('ALTER TABLE solicitud_gastos DROP COLUMN folio');
        $this->addSql('DROP TABLE solicitud_gastos_configuracion');
    }
}
