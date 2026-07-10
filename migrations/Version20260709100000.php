<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260709100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Folio por serie (contador independiente por departamento) + indice unico compuesto para permitir repetir folio entre series/periodos';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE solicitud_gastos_folio_serie (id INT AUTO_INCREMENT NOT NULL, serie VARCHAR(10) NOT NULL, contador_actual INT DEFAULT 0 NOT NULL, periodo_actual VARCHAR(20) DEFAULT NULL, UNIQUE INDEX UNIQ_SOLICITUD_GASTOS_FOLIO_SERIE_SERIE (serie), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE solicitud_gastos_configuracion ADD folio_alcance VARCHAR(20) DEFAULT \'global\' NOT NULL');

        $this->addSql('DROP INDEX UNIQ_SOLICITUD_GASTOS_FOLIO ON solicitud_gastos');
        $this->addSql('ALTER TABLE solicitud_gastos ADD folio_serie VARCHAR(10) DEFAULT \'\' NOT NULL, ADD folio_periodo VARCHAR(20) DEFAULT \'\' NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_sg_folio_serie_periodo ON solicitud_gastos (folio, folio_serie, folio_periodo)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_sg_folio_serie_periodo ON solicitud_gastos');
        $this->addSql('ALTER TABLE solicitud_gastos DROP COLUMN folio_serie, DROP COLUMN folio_periodo');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SOLICITUD_GASTOS_FOLIO ON solicitud_gastos (folio)');

        $this->addSql('ALTER TABLE solicitud_gastos_configuracion DROP COLUMN folio_alcance');

        $this->addSql('DROP TABLE solicitud_gastos_folio_serie');
    }
}
