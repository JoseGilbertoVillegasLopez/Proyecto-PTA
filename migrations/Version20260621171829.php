<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260621171829 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Agrega módulo indicadores_basicos al gestor de accesos';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departamento_indicadores_basicos DROP FOREIGN KEY FK_E7BEA87E55A7C08D');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos DROP FOREIGN KEY FK_E7BEA87E5A91C08D');
        $this->addSql('DROP INDEX `primary` ON departamento_indicadores_basicos');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos ADD CONSTRAINT FK_E7BEA87E55A7C08D FOREIGN KEY (indicadores_basicos_id) REFERENCES indicadores_basicos (id)');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos ADD CONSTRAINT FK_E7BEA87E5A91C08D FOREIGN KEY (departamento_id) REFERENCES departamento (id)');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos ADD PRIMARY KEY (indicadores_basicos_id, departamento_id)');
        $this->addSql('ALTER TABLE modulo_sistema CHANGE usa_encargado usa_encargado TINYINT(1) NOT NULL, CHANGE usa_acceso usa_acceso TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE reporte_indicador_actividad DROP FOREIGN KEY FK_REPORTE_IND_ACT_INDICADOR');
        $this->addSql('ALTER TABLE reporte_indicador_actividad DROP FOREIGN KEY FK_REPORTE_IND_ACT_TRIMESTRE');
        $this->addSql('DROP INDEX idx_reporte_ind_act_trimestre ON reporte_indicador_actividad');
        $this->addSql('CREATE INDEX IDX_8E7F279013BCE06B ON reporte_indicador_actividad (reporte_trimestre_id)');
        $this->addSql('DROP INDEX idx_reporte_ind_act_indicador ON reporte_indicador_actividad');
        $this->addSql('CREATE INDEX IDX_8E7F2790F5C2B885 ON reporte_indicador_actividad (indicador_basico_id)');
        $this->addSql('ALTER TABLE reporte_indicador_actividad ADD CONSTRAINT FK_REPORTE_IND_ACT_INDICADOR FOREIGN KEY (indicador_basico_id) REFERENCES indicadores_basicos (id)');
        $this->addSql('ALTER TABLE reporte_indicador_actividad ADD CONSTRAINT FK_REPORTE_IND_ACT_TRIMESTRE FOREIGN KEY (reporte_trimestre_id) REFERENCES reporte_indicador_trimestre (id)');
        $this->addSql('ALTER TABLE reporte_indicador_evidencia DROP FOREIGN KEY FK_REPORTE_IND_EVID_ACTIVIDAD');
        $this->addSql('DROP INDEX idx_reporte_ind_evid_actividad ON reporte_indicador_evidencia');
        $this->addSql('CREATE INDEX IDX_5A341AEA6014FACA ON reporte_indicador_evidencia (actividad_id)');
        $this->addSql('ALTER TABLE reporte_indicador_evidencia ADD CONSTRAINT FK_REPORTE_IND_EVID_ACTIVIDAD FOREIGN KEY (actividad_id) REFERENCES reporte_indicador_actividad (id)');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre DROP FOREIGN KEY FK_REPORTE_INDICADOR_DEPARTAMENTO');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre DROP FOREIGN KEY FK_REPORTE_INDICADOR_PERSONAL');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre DROP FOREIGN KEY FK_REPORTE_INDICADOR_PUESTO');
        $this->addSql('DROP INDEX idx_reporte_indicador_personal ON reporte_indicador_trimestre');
        $this->addSql('CREATE INDEX IDX_578B26DE5D430949 ON reporte_indicador_trimestre (personal_id)');
        $this->addSql('DROP INDEX idx_reporte_indicador_departamento ON reporte_indicador_trimestre');
        $this->addSql('CREATE INDEX IDX_578B26DE5A91C08D ON reporte_indicador_trimestre (departamento_id)');
        $this->addSql('DROP INDEX idx_reporte_indicador_puesto ON reporte_indicador_trimestre');
        $this->addSql('CREATE INDEX IDX_578B26DE5035E9DA ON reporte_indicador_trimestre (puesto_id)');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre ADD CONSTRAINT FK_REPORTE_INDICADOR_DEPARTAMENTO FOREIGN KEY (departamento_id) REFERENCES departamento (id)');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre ADD CONSTRAINT FK_REPORTE_INDICADOR_PERSONAL FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre ADD CONSTRAINT FK_REPORTE_INDICADOR_PUESTO FOREIGN KEY (puesto_id) REFERENCES puesto (id)');
        $this->addSql('ALTER TABLE reporte_indicadores DROP FOREIGN KEY FK_REPORTE_INDICADORES_CREADO_POR');
        $this->addSql('DROP INDEX idx_reporte_indicadores_creado_por ON reporte_indicadores');
        $this->addSql('CREATE INDEX IDX_DC1850C1FE35D8C4 ON reporte_indicadores (creado_por_id)');
        $this->addSql('ALTER TABLE reporte_indicadores ADD CONSTRAINT FK_REPORTE_INDICADORES_CREADO_POR FOREIGN KEY (creado_por_id) REFERENCES user (id)');
        $this->addSql("INSERT INTO modulo_sistema (slug, label, descripcion, activo, usa_encargado, usa_acceso) VALUES ('indicadores_basicos', 'Indicadores Básicos', 'Catálogo de indicadores básicos del instituto', 1, 1, 0)");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE departamento_indicadores_basicos DROP FOREIGN KEY FK_E7BEA87E55A7C08D');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos DROP FOREIGN KEY FK_E7BEA87E5A91C08D');
        $this->addSql('DROP INDEX `PRIMARY` ON departamento_indicadores_basicos');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos ADD CONSTRAINT FK_E7BEA87E55A7C08D FOREIGN KEY (indicadores_basicos_id) REFERENCES indicadores_basicos (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos ADD CONSTRAINT FK_E7BEA87E5A91C08D FOREIGN KEY (departamento_id) REFERENCES departamento (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos ADD PRIMARY KEY (departamento_id, indicadores_basicos_id)');
        $this->addSql('ALTER TABLE modulo_sistema CHANGE usa_encargado usa_encargado TINYINT(1) DEFAULT 1 NOT NULL, CHANGE usa_acceso usa_acceso TINYINT(1) DEFAULT 1 NOT NULL');
        $this->addSql('ALTER TABLE reporte_indicadores DROP FOREIGN KEY FK_DC1850C1FE35D8C4');
        $this->addSql('DROP INDEX idx_dc1850c1fe35d8c4 ON reporte_indicadores');
        $this->addSql('CREATE INDEX IDX_REPORTE_INDICADORES_CREADO_POR ON reporte_indicadores (creado_por_id)');
        $this->addSql('ALTER TABLE reporte_indicadores ADD CONSTRAINT FK_DC1850C1FE35D8C4 FOREIGN KEY (creado_por_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reporte_indicador_actividad DROP FOREIGN KEY FK_8E7F279013BCE06B');
        $this->addSql('ALTER TABLE reporte_indicador_actividad DROP FOREIGN KEY FK_8E7F2790F5C2B885');
        $this->addSql('DROP INDEX idx_8e7f2790f5c2b885 ON reporte_indicador_actividad');
        $this->addSql('CREATE INDEX IDX_REPORTE_IND_ACT_INDICADOR ON reporte_indicador_actividad (indicador_basico_id)');
        $this->addSql('DROP INDEX idx_8e7f279013bce06b ON reporte_indicador_actividad');
        $this->addSql('CREATE INDEX IDX_REPORTE_IND_ACT_TRIMESTRE ON reporte_indicador_actividad (reporte_trimestre_id)');
        $this->addSql('ALTER TABLE reporte_indicador_actividad ADD CONSTRAINT FK_8E7F279013BCE06B FOREIGN KEY (reporte_trimestre_id) REFERENCES reporte_indicador_trimestre (id)');
        $this->addSql('ALTER TABLE reporte_indicador_actividad ADD CONSTRAINT FK_8E7F2790F5C2B885 FOREIGN KEY (indicador_basico_id) REFERENCES indicadores_basicos (id)');
        $this->addSql('ALTER TABLE reporte_indicador_evidencia DROP FOREIGN KEY FK_5A341AEA6014FACA');
        $this->addSql('DROP INDEX idx_5a341aea6014faca ON reporte_indicador_evidencia');
        $this->addSql('CREATE INDEX IDX_REPORTE_IND_EVID_ACTIVIDAD ON reporte_indicador_evidencia (actividad_id)');
        $this->addSql('ALTER TABLE reporte_indicador_evidencia ADD CONSTRAINT FK_5A341AEA6014FACA FOREIGN KEY (actividad_id) REFERENCES reporte_indicador_actividad (id)');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre DROP FOREIGN KEY FK_578B26DE5D430949');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre DROP FOREIGN KEY FK_578B26DE5A91C08D');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre DROP FOREIGN KEY FK_578B26DE5035E9DA');
        $this->addSql('DROP INDEX idx_578b26de5d430949 ON reporte_indicador_trimestre');
        $this->addSql('CREATE INDEX IDX_REPORTE_INDICADOR_PERSONAL ON reporte_indicador_trimestre (personal_id)');
        $this->addSql('DROP INDEX idx_578b26de5a91c08d ON reporte_indicador_trimestre');
        $this->addSql('CREATE INDEX IDX_REPORTE_INDICADOR_DEPARTAMENTO ON reporte_indicador_trimestre (departamento_id)');
        $this->addSql('DROP INDEX idx_578b26de5035e9da ON reporte_indicador_trimestre');
        $this->addSql('CREATE INDEX IDX_REPORTE_INDICADOR_PUESTO ON reporte_indicador_trimestre (puesto_id)');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre ADD CONSTRAINT FK_578B26DE5D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre ADD CONSTRAINT FK_578B26DE5A91C08D FOREIGN KEY (departamento_id) REFERENCES departamento (id)');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre ADD CONSTRAINT FK_578B26DE5035E9DA FOREIGN KEY (puesto_id) REFERENCES puesto (id)');
        $this->addSql("DELETE FROM modulo_sistema WHERE slug = 'indicadores_basicos'");
    }
}
