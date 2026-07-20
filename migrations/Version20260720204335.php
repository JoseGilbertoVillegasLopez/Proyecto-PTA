<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260720204335 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE acciones (id INT AUTO_INCREMENT NOT NULL, encabezado_id INT NOT NULL, accion LONGTEXT NOT NULL, periodo JSON NOT NULL COMMENT \'(DC2Type:json)\', meses_cumplidos JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', indicador INT NOT NULL, INDEX IDX_29F5FFE7DD017133 (encabezado_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ciclo_indicadores (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(20) NOT NULL, fecha_apertura DATE NOT NULL, fecha_cierre DATE NOT NULL, activo TINYINT(1) DEFAULT 0 NOT NULL, visible TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_CICLO_INDICADORES_NOMBRE (nombre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE departamento (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, activo TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE encabezado (id INT AUTO_INCREMENT NOT NULL, responsable_id INT NOT NULL, objetivo LONGTEXT NOT NULL, nombre VARCHAR(255) NOT NULL, fecha_creacion DATE NOT NULL, fecha_concluido DATE DEFAULT NULL, status TINYINT(1) NOT NULL, anio_ejecucion INT NOT NULL, INDEX IDX_B6A5789453C59D72 (responsable_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE grupo_indicadores_basicos (id INT AUTO_INCREMENT NOT NULL, grupo VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE historial_acciones (id INT AUTO_INCREMENT NOT NULL, accion_id INT NOT NULL, mes INT NOT NULL, valor INT NOT NULL, motivo LONGTEXT DEFAULT NULL, fecha DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F1FB65403F4B5275 (accion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE historial_indicador_valor (id INT AUTO_INCREMENT NOT NULL, indicador_id INT NOT NULL, mes INT NOT NULL, valor NUMERIC(10, 2) NOT NULL, motivo LONGTEXT DEFAULT NULL, fecha DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2C9ACC9C47D487D1 (indicador_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE indicadores (id INT AUTO_INCREMENT NOT NULL, encabezado_id INT NOT NULL, indicador VARCHAR(255) NOT NULL, formula VARCHAR(255) NOT NULL, valor NUMERIC(10, 2) NOT NULL, periodo VARCHAR(255) NOT NULL, indice INT NOT NULL, tendencia VARCHAR(255) NOT NULL, valor_base NUMERIC(10, 2) NOT NULL, es_porcentaje TINYINT(1) DEFAULT 0 NOT NULL, captura_en_porcentaje TINYINT(1) DEFAULT 0 NOT NULL, valor_mensual JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', INDEX IDX_B1E9F9AFDD017133 (encabezado_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE indicadores_basicos (id INT AUTO_INCREMENT NOT NULL, grupo_id INT DEFAULT NULL, nombre_indicador VARCHAR(255) NOT NULL, formula VARCHAR(255) NOT NULL, observaciones VARCHAR(255) NOT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, INDEX IDX_31665EF59C833003 (grupo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE departamento_indicadores_basicos (indicadores_basicos_id INT NOT NULL, departamento_id INT NOT NULL, INDEX IDX_E7BEA87E55A7C08D (indicadores_basicos_id), INDEX IDX_E7BEA87E5A91C08D (departamento_id), PRIMARY KEY(indicadores_basicos_id, departamento_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE modulo_acceso (id INT AUTO_INCREMENT NOT NULL, modulo_id INT NOT NULL, puesto_id INT NOT NULL, tipo VARCHAR(20) NOT NULL, cargo VARCHAR(20) DEFAULT NULL, INDEX IDX_29AB2BFCC07F55F5 (modulo_id), INDEX IDX_29AB2BFC5035E9DA (puesto_id), UNIQUE INDEX uniq_modulo_puesto_tipo (modulo_id, puesto_id, tipo), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE modulo_sistema (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(100) NOT NULL, label VARCHAR(255) NOT NULL, descripcion LONGTEXT DEFAULT NULL, activo TINYINT(1) NOT NULL, usa_encargado TINYINT(1) NOT NULL, usa_acceso TINYINT(1) NOT NULL, usa_cargo_encargado TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_AF971FE6989D9B62 (slug), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nombramiento (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, archivo VARCHAR(255) NOT NULL, nombre_original VARCHAR(255) DEFAULT NULL, tipo VARCHAR(100) NOT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, fecha_subida DATE NOT NULL, fecha_desactivacion DATE DEFAULT NULL, INDEX IDX_1405C4415D430949 (personal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE partidas_presupuestales (id INT AUTO_INCREMENT NOT NULL, capitulo VARCHAR(255) NOT NULL, partida VARCHAR(255) NOT NULL, descripcion VARCHAR(255) NOT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE personal (id INT AUTO_INCREMENT NOT NULL, puesto_id INT NOT NULL, departamento_id INT NOT NULL, nombre VARCHAR(255) NOT NULL, ap_paterno VARCHAR(255) NOT NULL, ap_materno VARCHAR(255) NOT NULL, correo VARCHAR(255) NOT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, INDEX IDX_F18A6D845035E9DA (puesto_id), INDEX IDX_F18A6D845A91C08D (departamento_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE proceso_clave (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, pei VARCHAR(255) NOT NULL, paig VARCHAR(255) NOT NULL, meta_pdi_pta VARCHAR(255) NOT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE proceso_estrategico (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE puesto (id INT AUTO_INCREMENT NOT NULL, supervisor_directo_id INT DEFAULT NULL, nombre VARCHAR(255) NOT NULL, activo TINYINT(1) NOT NULL, serie VARCHAR(10) DEFAULT NULL, INDEX IDX_47C3D2DE7EA96164 (supervisor_directo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporte_indicador_actividad (id INT AUTO_INCREMENT NOT NULL, reporte_trimestre_id INT NOT NULL, indicador_basico_id INT DEFAULT NULL, accion LONGTEXT NOT NULL, descripcion LONGTEXT NOT NULL, INDEX IDX_8E7F279013BCE06B (reporte_trimestre_id), INDEX IDX_8E7F2790F5C2B885 (indicador_basico_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporte_indicador_evidencia (id INT AUTO_INCREMENT NOT NULL, actividad_id INT NOT NULL, archivo_nombre_original VARCHAR(255) NOT NULL, archivo_nombre_guardado VARCHAR(255) NOT NULL, ruta VARCHAR(255) NOT NULL, mime_type VARCHAR(120) NOT NULL, extension VARCHAR(20) NOT NULL, tamano INT NOT NULL, orden INT NOT NULL, creado_fecha DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', INDEX IDX_5A341AEA6014FACA (actividad_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporte_indicador_trimestre (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, departamento_id INT NOT NULL, puesto_id INT NOT NULL, anio INT NOT NULL, trimestre INT NOT NULL, estado VARCHAR(20) DEFAULT \'borrador\' NOT NULL, creado_fecha DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', entregado_fecha DATE DEFAULT NULL COMMENT \'(DC2Type:date_immutable)\', INDEX IDX_578B26DE5D430949 (personal_id), INDEX IDX_578B26DE5A91C08D (departamento_id), INDEX IDX_578B26DE5035E9DA (puesto_id), UNIQUE INDEX UNIQ_REPORTE_INDICADOR_PERSONAL_ANIO_TRIMESTRE (personal_id, anio, trimestre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporte_indicadores (id INT AUTO_INCREMENT NOT NULL, creado_por_id INT NOT NULL, titulo VARCHAR(160) NOT NULL, estado VARCHAR(40) DEFAULT \'Borrador\' NOT NULL, creado_fecha DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', actualizado_fecha DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_DC1850C1FE35D8C4 (creado_por_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporte_pta_accion (id INT AUTO_INCREMENT NOT NULL, reporte_indicador_id INT NOT NULL, proceso_estrategico_id INT DEFAULT NULL, proceso_clave_id INT DEFAULT NULL, accion LONGTEXT NOT NULL, INDEX IDX_66DD814EEDB33A27 (reporte_indicador_id), INDEX IDX_66DD814E8DFC750A (proceso_estrategico_id), INDEX IDX_66DD814E9CF1B3C4 (proceso_clave_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporte_pta_accion_partida (id INT AUTO_INCREMENT NOT NULL, reporte_accion_id INT NOT NULL, partida_presupuestal_id INT DEFAULT NULL, cantidad NUMERIC(12, 2) NOT NULL, INDEX IDX_772D05F4AB50CCCB (reporte_accion_id), INDEX IDX_772D05F490B9C97D (partida_presupuestal_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporte_pta_evidencias (id INT AUTO_INCREMENT NOT NULL, reporte_pta_indicador_id INT NOT NULL, imagenes JSON NOT NULL COMMENT \'(DC2Type:json)\', descripcion LONGTEXT NOT NULL, INDEX IDX_4F70D592CEE01988 (reporte_pta_indicador_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporte_pta_indicador (id INT AUTO_INCREMENT NOT NULL, reporte_trimestre_id INT NOT NULL, indicador_basico_id INT DEFAULT NULL, indicador_pta_id INT NOT NULL, responsable_puesto_id INT NOT NULL, unidad_medida VARCHAR(255) NOT NULL, meta NUMERIC(10, 2) NOT NULL, resultado NUMERIC(10, 2) NOT NULL, porcentaje_avance NUMERIC(5, 2) NOT NULL, formula LONGTEXT DEFAULT NULL, medio_verificacion LONGTEXT NOT NULL, meta_cumplida LONGTEXT NOT NULL, formula_descripcion LONGTEXT NOT NULL, INDEX IDX_34E60E4913BCE06B (reporte_trimestre_id), INDEX IDX_34E60E49F5C2B885 (indicador_basico_id), INDEX IDX_34E60E498999058 (indicador_pta_id), INDEX IDX_34E60E49AD73B8CC (responsable_puesto_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reporte_pta_trimestre (id INT AUTO_INCREMENT NOT NULL, encabezado_id INT NOT NULL, anio INT NOT NULL, trimestre INT NOT NULL, estado TINYINT(1) DEFAULT 0 NOT NULL, creado_fecha DATETIME NOT NULL, entregado_fecha DATETIME DEFAULT NULL, INDEX IDX_ADF28CC2DD017133 (encabezado_id), UNIQUE INDEX uniq_reporte (encabezado_id, trimestre), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE responsables (id INT AUTO_INCREMENT NOT NULL, encabezado_id INT NOT NULL, supervisor_id INT NOT NULL, aval_id INT NOT NULL, UNIQUE INDEX UNIQ_853808A5DD017133 (encabezado_id), INDEX IDX_853808A519E9AC5F (supervisor_id), INDEX IDX_853808A521747C97 (aval_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE semaforo_indicadores (id INT AUTO_INCREMENT NOT NULL, ciclo_indicadores_id INT NOT NULL, id_indicadorbasico INT NOT NULL, cantidad1 NUMERIC(14, 2) DEFAULT NULL, cantidad2 NUMERIC(14, 2) DEFAULT NULL, resultado_ciclo NUMERIC(14, 2) DEFAULT NULL, INDEX IDX_10FE96AFC5CB60F7 (ciclo_indicadores_id), INDEX IDX_10FE96AFFEA9D90D (id_indicadorbasico), UNIQUE INDEX UNIQ_SEMAFORO_INDICADOR_CICLO (id_indicadorbasico, ciclo_indicadores_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE semaforo_indicadores_media (id INT AUTO_INCREMENT NOT NULL, id_indicadorbasico INT NOT NULL, media_estatal NUMERIC(14, 2) DEFAULT NULL, media_nacional NUMERIC(14, 2) DEFAULT NULL, UNIQUE INDEX UNIQ_SEMAFORO_MEDIA_INDICADOR (id_indicadorbasico), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE solicitud_gastos (id INT AUTO_INCREMENT NOT NULL, solicitante_id INT NOT NULL, tipo_solicitud_id INT NOT NULL, proceso_estrategico_id INT DEFAULT NULL, proceso_clave_id INT DEFAULT NULL, banco_id INT DEFAULT NULL, jefe_area_id INT DEFAULT NULL, autoriza_id INT DEFAULT NULL, fecha_solicitud DATETIME NOT NULL, fecha_necesita DATE NOT NULL, transferencia_en_beneficio_de VARCHAR(255) DEFAULT NULL, cta_clave_beneficiario VARCHAR(255) DEFAULT NULL, por_concepto_de LONGTEXT NOT NULL, cantidad_total NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, estado VARCHAR(20) DEFAULT \'pendiente\' NOT NULL, folio INT DEFAULT NULL, folio_serie VARCHAR(10) DEFAULT \'\' NOT NULL, folio_periodo VARCHAR(20) DEFAULT \'\' NOT NULL, documento_verificacion VARCHAR(60) DEFAULT NULL, documento_verificacion_descripcion VARCHAR(255) DEFAULT NULL, INDEX IDX_23AA7F5FC680A87 (solicitante_id), INDEX IDX_23AA7F5FAFEA88E4 (tipo_solicitud_id), INDEX IDX_23AA7F5F8DFC750A (proceso_estrategico_id), INDEX IDX_23AA7F5F9CF1B3C4 (proceso_clave_id), INDEX IDX_23AA7F5FCC04A73E (banco_id), INDEX IDX_23AA7F5FFE5BB628 (jefe_area_id), INDEX IDX_23AA7F5FB3CD7DBC (autoriza_id), UNIQUE INDEX uniq_sg_folio_serie_periodo (folio, folio_serie, folio_periodo), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE solicitud_gastos_bancos (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, estado VARCHAR(20) DEFAULT \'activo\' NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE solicitud_gastos_comprobante (id INT AUTO_INCREMENT NOT NULL, solicitud_id INT NOT NULL, archivo_nombre_original VARCHAR(255) NOT NULL, archivo_nombre_guardado VARCHAR(255) NOT NULL, ruta VARCHAR(255) NOT NULL, mime_type VARCHAR(120) NOT NULL, extension VARCHAR(20) NOT NULL, tamano INT NOT NULL, creado_fecha DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', UNIQUE INDEX UNIQ_2D501B4A1CB9D6E4 (solicitud_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE solicitud_gastos_configuracion (id INT NOT NULL, criterio_aprobacion VARCHAR(20) DEFAULT \'unanime\' NOT NULL, mostrar_motivo_rechazo TINYINT(1) DEFAULT 0 NOT NULL, folio_aplica_a VARCHAR(30) DEFAULT \'solo_aceptadas\' NOT NULL, folio_ciclo_reinicio VARCHAR(20) DEFAULT \'continuo\' NOT NULL, folio_alcance VARCHAR(20) DEFAULT \'global\' NOT NULL, folio_contador_actual INT DEFAULT 0 NOT NULL, folio_periodo_actual VARCHAR(20) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE solicitud_gastos_evidencia (id INT AUTO_INCREMENT NOT NULL, solicitud_id INT NOT NULL, archivo_nombre_original VARCHAR(255) NOT NULL, archivo_nombre_guardado VARCHAR(255) NOT NULL, ruta VARCHAR(255) NOT NULL, mime_type VARCHAR(120) NOT NULL, extension VARCHAR(20) NOT NULL, tamano INT NOT NULL, orden INT NOT NULL, creado_fecha DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\', INDEX IDX_B6F828E51CB9D6E4 (solicitud_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE solicitud_gastos_folio_serie (id INT AUTO_INCREMENT NOT NULL, serie VARCHAR(10) NOT NULL, contador_actual INT DEFAULT 0 NOT NULL, periodo_actual VARCHAR(20) DEFAULT NULL, UNIQUE INDEX UNIQ_12496C85AA3A9334 (serie), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE solicitud_gastos_partida (id INT AUTO_INCREMENT NOT NULL, solicitud_id INT NOT NULL, partida_id INT NOT NULL, monto NUMERIC(10, 2) NOT NULL, INDEX IDX_75499F851CB9D6E4 (solicitud_id), INDEX IDX_75499F85F15A1987 (partida_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE solicitud_gastos_revision (id INT AUTO_INCREMENT NOT NULL, solicitud_id INT NOT NULL, personal_id INT DEFAULT NULL, cargo VARCHAR(20) NOT NULL, estado VARCHAR(20) DEFAULT \'pendiente\' NOT NULL, comentario LONGTEXT DEFAULT NULL, fecha_apertura DATETIME DEFAULT NULL, fecha_resolucion DATETIME DEFAULT NULL, INDEX IDX_F9DBA68F1CB9D6E4 (solicitud_id), INDEX IDX_F9DBA68F5D430949 (personal_id), UNIQUE INDEX uniq_solicitud_cargo (solicitud_id, cargo), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tipo_solicitud (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, personal_id INT NOT NULL, usuario VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, cambiar_password TINYINT(1) DEFAULT 1 NOT NULL, activo TINYINT(1) DEFAULT 1 NOT NULL, UNIQUE INDEX UNIQ_8D93D6495D430949 (personal_id), UNIQUE INDEX UNIQ_IDENTIFIER_USUARIO (usuario), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE acciones ADD CONSTRAINT FK_29F5FFE7DD017133 FOREIGN KEY (encabezado_id) REFERENCES encabezado (id)');
        $this->addSql('ALTER TABLE encabezado ADD CONSTRAINT FK_B6A5789453C59D72 FOREIGN KEY (responsable_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE historial_acciones ADD CONSTRAINT FK_F1FB65403F4B5275 FOREIGN KEY (accion_id) REFERENCES acciones (id)');
        $this->addSql('ALTER TABLE historial_indicador_valor ADD CONSTRAINT FK_2C9ACC9C47D487D1 FOREIGN KEY (indicador_id) REFERENCES indicadores (id)');
        $this->addSql('ALTER TABLE indicadores ADD CONSTRAINT FK_B1E9F9AFDD017133 FOREIGN KEY (encabezado_id) REFERENCES encabezado (id)');
        $this->addSql('ALTER TABLE indicadores_basicos ADD CONSTRAINT FK_31665EF59C833003 FOREIGN KEY (grupo_id) REFERENCES grupo_indicadores_basicos (id)');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos ADD CONSTRAINT FK_E7BEA87E55A7C08D FOREIGN KEY (indicadores_basicos_id) REFERENCES indicadores_basicos (id)');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos ADD CONSTRAINT FK_E7BEA87E5A91C08D FOREIGN KEY (departamento_id) REFERENCES departamento (id)');
        $this->addSql('ALTER TABLE modulo_acceso ADD CONSTRAINT FK_29AB2BFCC07F55F5 FOREIGN KEY (modulo_id) REFERENCES modulo_sistema (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE modulo_acceso ADD CONSTRAINT FK_29AB2BFC5035E9DA FOREIGN KEY (puesto_id) REFERENCES puesto (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE nombramiento ADD CONSTRAINT FK_1405C4415D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE personal ADD CONSTRAINT FK_F18A6D845035E9DA FOREIGN KEY (puesto_id) REFERENCES puesto (id)');
        $this->addSql('ALTER TABLE personal ADD CONSTRAINT FK_F18A6D845A91C08D FOREIGN KEY (departamento_id) REFERENCES departamento (id)');
        $this->addSql('ALTER TABLE puesto ADD CONSTRAINT FK_47C3D2DE7EA96164 FOREIGN KEY (supervisor_directo_id) REFERENCES puesto (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE reporte_indicador_actividad ADD CONSTRAINT FK_8E7F279013BCE06B FOREIGN KEY (reporte_trimestre_id) REFERENCES reporte_indicador_trimestre (id)');
        $this->addSql('ALTER TABLE reporte_indicador_actividad ADD CONSTRAINT FK_8E7F2790F5C2B885 FOREIGN KEY (indicador_basico_id) REFERENCES indicadores_basicos (id)');
        $this->addSql('ALTER TABLE reporte_indicador_evidencia ADD CONSTRAINT FK_5A341AEA6014FACA FOREIGN KEY (actividad_id) REFERENCES reporte_indicador_actividad (id)');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre ADD CONSTRAINT FK_578B26DE5D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre ADD CONSTRAINT FK_578B26DE5A91C08D FOREIGN KEY (departamento_id) REFERENCES departamento (id)');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre ADD CONSTRAINT FK_578B26DE5035E9DA FOREIGN KEY (puesto_id) REFERENCES puesto (id)');
        $this->addSql('ALTER TABLE reporte_indicadores ADD CONSTRAINT FK_DC1850C1FE35D8C4 FOREIGN KEY (creado_por_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reporte_pta_accion ADD CONSTRAINT FK_66DD814EEDB33A27 FOREIGN KEY (reporte_indicador_id) REFERENCES reporte_pta_indicador (id)');
        $this->addSql('ALTER TABLE reporte_pta_accion ADD CONSTRAINT FK_66DD814E8DFC750A FOREIGN KEY (proceso_estrategico_id) REFERENCES proceso_estrategico (id)');
        $this->addSql('ALTER TABLE reporte_pta_accion ADD CONSTRAINT FK_66DD814E9CF1B3C4 FOREIGN KEY (proceso_clave_id) REFERENCES proceso_clave (id)');
        $this->addSql('ALTER TABLE reporte_pta_accion_partida ADD CONSTRAINT FK_772D05F4AB50CCCB FOREIGN KEY (reporte_accion_id) REFERENCES reporte_pta_accion (id)');
        $this->addSql('ALTER TABLE reporte_pta_accion_partida ADD CONSTRAINT FK_772D05F490B9C97D FOREIGN KEY (partida_presupuestal_id) REFERENCES partidas_presupuestales (id)');
        $this->addSql('ALTER TABLE reporte_pta_evidencias ADD CONSTRAINT FK_4F70D592CEE01988 FOREIGN KEY (reporte_pta_indicador_id) REFERENCES reporte_pta_indicador (id)');
        $this->addSql('ALTER TABLE reporte_pta_indicador ADD CONSTRAINT FK_34E60E4913BCE06B FOREIGN KEY (reporte_trimestre_id) REFERENCES reporte_pta_trimestre (id)');
        $this->addSql('ALTER TABLE reporte_pta_indicador ADD CONSTRAINT FK_34E60E49F5C2B885 FOREIGN KEY (indicador_basico_id) REFERENCES indicadores_basicos (id)');
        $this->addSql('ALTER TABLE reporte_pta_indicador ADD CONSTRAINT FK_34E60E498999058 FOREIGN KEY (indicador_pta_id) REFERENCES indicadores (id)');
        $this->addSql('ALTER TABLE reporte_pta_indicador ADD CONSTRAINT FK_34E60E49AD73B8CC FOREIGN KEY (responsable_puesto_id) REFERENCES puesto (id)');
        $this->addSql('ALTER TABLE reporte_pta_trimestre ADD CONSTRAINT FK_ADF28CC2DD017133 FOREIGN KEY (encabezado_id) REFERENCES encabezado (id)');
        $this->addSql('ALTER TABLE responsables ADD CONSTRAINT FK_853808A5DD017133 FOREIGN KEY (encabezado_id) REFERENCES encabezado (id)');
        $this->addSql('ALTER TABLE responsables ADD CONSTRAINT FK_853808A519E9AC5F FOREIGN KEY (supervisor_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE responsables ADD CONSTRAINT FK_853808A521747C97 FOREIGN KEY (aval_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE semaforo_indicadores ADD CONSTRAINT FK_10FE96AFC5CB60F7 FOREIGN KEY (ciclo_indicadores_id) REFERENCES ciclo_indicadores (id)');
        $this->addSql('ALTER TABLE semaforo_indicadores ADD CONSTRAINT FK_10FE96AFFEA9D90D FOREIGN KEY (id_indicadorbasico) REFERENCES indicadores_basicos (id)');
        $this->addSql('ALTER TABLE semaforo_indicadores_media ADD CONSTRAINT FK_2C87FEFFFEA9D90D FOREIGN KEY (id_indicadorbasico) REFERENCES indicadores_basicos (id)');
        $this->addSql('ALTER TABLE solicitud_gastos ADD CONSTRAINT FK_23AA7F5FC680A87 FOREIGN KEY (solicitante_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE solicitud_gastos ADD CONSTRAINT FK_23AA7F5FAFEA88E4 FOREIGN KEY (tipo_solicitud_id) REFERENCES tipo_solicitud (id)');
        $this->addSql('ALTER TABLE solicitud_gastos ADD CONSTRAINT FK_23AA7F5F8DFC750A FOREIGN KEY (proceso_estrategico_id) REFERENCES proceso_estrategico (id)');
        $this->addSql('ALTER TABLE solicitud_gastos ADD CONSTRAINT FK_23AA7F5F9CF1B3C4 FOREIGN KEY (proceso_clave_id) REFERENCES proceso_clave (id)');
        $this->addSql('ALTER TABLE solicitud_gastos ADD CONSTRAINT FK_23AA7F5FCC04A73E FOREIGN KEY (banco_id) REFERENCES solicitud_gastos_bancos (id)');
        $this->addSql('ALTER TABLE solicitud_gastos ADD CONSTRAINT FK_23AA7F5FFE5BB628 FOREIGN KEY (jefe_area_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE solicitud_gastos ADD CONSTRAINT FK_23AA7F5FB3CD7DBC FOREIGN KEY (autoriza_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE solicitud_gastos_comprobante ADD CONSTRAINT FK_2D501B4A1CB9D6E4 FOREIGN KEY (solicitud_id) REFERENCES solicitud_gastos (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE solicitud_gastos_evidencia ADD CONSTRAINT FK_B6F828E51CB9D6E4 FOREIGN KEY (solicitud_id) REFERENCES solicitud_gastos (id)');
        $this->addSql('ALTER TABLE solicitud_gastos_partida ADD CONSTRAINT FK_75499F851CB9D6E4 FOREIGN KEY (solicitud_id) REFERENCES solicitud_gastos (id)');
        $this->addSql('ALTER TABLE solicitud_gastos_partida ADD CONSTRAINT FK_75499F85F15A1987 FOREIGN KEY (partida_id) REFERENCES partidas_presupuestales (id)');
        $this->addSql('ALTER TABLE solicitud_gastos_revision ADD CONSTRAINT FK_F9DBA68F1CB9D6E4 FOREIGN KEY (solicitud_id) REFERENCES solicitud_gastos (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE solicitud_gastos_revision ADD CONSTRAINT FK_F9DBA68F5D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6495D430949 FOREIGN KEY (personal_id) REFERENCES personal (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE acciones DROP FOREIGN KEY FK_29F5FFE7DD017133');
        $this->addSql('ALTER TABLE encabezado DROP FOREIGN KEY FK_B6A5789453C59D72');
        $this->addSql('ALTER TABLE historial_acciones DROP FOREIGN KEY FK_F1FB65403F4B5275');
        $this->addSql('ALTER TABLE historial_indicador_valor DROP FOREIGN KEY FK_2C9ACC9C47D487D1');
        $this->addSql('ALTER TABLE indicadores DROP FOREIGN KEY FK_B1E9F9AFDD017133');
        $this->addSql('ALTER TABLE indicadores_basicos DROP FOREIGN KEY FK_31665EF59C833003');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos DROP FOREIGN KEY FK_E7BEA87E55A7C08D');
        $this->addSql('ALTER TABLE departamento_indicadores_basicos DROP FOREIGN KEY FK_E7BEA87E5A91C08D');
        $this->addSql('ALTER TABLE modulo_acceso DROP FOREIGN KEY FK_29AB2BFCC07F55F5');
        $this->addSql('ALTER TABLE modulo_acceso DROP FOREIGN KEY FK_29AB2BFC5035E9DA');
        $this->addSql('ALTER TABLE nombramiento DROP FOREIGN KEY FK_1405C4415D430949');
        $this->addSql('ALTER TABLE personal DROP FOREIGN KEY FK_F18A6D845035E9DA');
        $this->addSql('ALTER TABLE personal DROP FOREIGN KEY FK_F18A6D845A91C08D');
        $this->addSql('ALTER TABLE puesto DROP FOREIGN KEY FK_47C3D2DE7EA96164');
        $this->addSql('ALTER TABLE reporte_indicador_actividad DROP FOREIGN KEY FK_8E7F279013BCE06B');
        $this->addSql('ALTER TABLE reporte_indicador_actividad DROP FOREIGN KEY FK_8E7F2790F5C2B885');
        $this->addSql('ALTER TABLE reporte_indicador_evidencia DROP FOREIGN KEY FK_5A341AEA6014FACA');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre DROP FOREIGN KEY FK_578B26DE5D430949');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre DROP FOREIGN KEY FK_578B26DE5A91C08D');
        $this->addSql('ALTER TABLE reporte_indicador_trimestre DROP FOREIGN KEY FK_578B26DE5035E9DA');
        $this->addSql('ALTER TABLE reporte_indicadores DROP FOREIGN KEY FK_DC1850C1FE35D8C4');
        $this->addSql('ALTER TABLE reporte_pta_accion DROP FOREIGN KEY FK_66DD814EEDB33A27');
        $this->addSql('ALTER TABLE reporte_pta_accion DROP FOREIGN KEY FK_66DD814E8DFC750A');
        $this->addSql('ALTER TABLE reporte_pta_accion DROP FOREIGN KEY FK_66DD814E9CF1B3C4');
        $this->addSql('ALTER TABLE reporte_pta_accion_partida DROP FOREIGN KEY FK_772D05F4AB50CCCB');
        $this->addSql('ALTER TABLE reporte_pta_accion_partida DROP FOREIGN KEY FK_772D05F490B9C97D');
        $this->addSql('ALTER TABLE reporte_pta_evidencias DROP FOREIGN KEY FK_4F70D592CEE01988');
        $this->addSql('ALTER TABLE reporte_pta_indicador DROP FOREIGN KEY FK_34E60E4913BCE06B');
        $this->addSql('ALTER TABLE reporte_pta_indicador DROP FOREIGN KEY FK_34E60E49F5C2B885');
        $this->addSql('ALTER TABLE reporte_pta_indicador DROP FOREIGN KEY FK_34E60E498999058');
        $this->addSql('ALTER TABLE reporte_pta_indicador DROP FOREIGN KEY FK_34E60E49AD73B8CC');
        $this->addSql('ALTER TABLE reporte_pta_trimestre DROP FOREIGN KEY FK_ADF28CC2DD017133');
        $this->addSql('ALTER TABLE responsables DROP FOREIGN KEY FK_853808A5DD017133');
        $this->addSql('ALTER TABLE responsables DROP FOREIGN KEY FK_853808A519E9AC5F');
        $this->addSql('ALTER TABLE responsables DROP FOREIGN KEY FK_853808A521747C97');
        $this->addSql('ALTER TABLE semaforo_indicadores DROP FOREIGN KEY FK_10FE96AFC5CB60F7');
        $this->addSql('ALTER TABLE semaforo_indicadores DROP FOREIGN KEY FK_10FE96AFFEA9D90D');
        $this->addSql('ALTER TABLE semaforo_indicadores_media DROP FOREIGN KEY FK_2C87FEFFFEA9D90D');
        $this->addSql('ALTER TABLE solicitud_gastos DROP FOREIGN KEY FK_23AA7F5FC680A87');
        $this->addSql('ALTER TABLE solicitud_gastos DROP FOREIGN KEY FK_23AA7F5FAFEA88E4');
        $this->addSql('ALTER TABLE solicitud_gastos DROP FOREIGN KEY FK_23AA7F5F8DFC750A');
        $this->addSql('ALTER TABLE solicitud_gastos DROP FOREIGN KEY FK_23AA7F5F9CF1B3C4');
        $this->addSql('ALTER TABLE solicitud_gastos DROP FOREIGN KEY FK_23AA7F5FCC04A73E');
        $this->addSql('ALTER TABLE solicitud_gastos DROP FOREIGN KEY FK_23AA7F5FFE5BB628');
        $this->addSql('ALTER TABLE solicitud_gastos DROP FOREIGN KEY FK_23AA7F5FB3CD7DBC');
        $this->addSql('ALTER TABLE solicitud_gastos_comprobante DROP FOREIGN KEY FK_2D501B4A1CB9D6E4');
        $this->addSql('ALTER TABLE solicitud_gastos_evidencia DROP FOREIGN KEY FK_B6F828E51CB9D6E4');
        $this->addSql('ALTER TABLE solicitud_gastos_partida DROP FOREIGN KEY FK_75499F851CB9D6E4');
        $this->addSql('ALTER TABLE solicitud_gastos_partida DROP FOREIGN KEY FK_75499F85F15A1987');
        $this->addSql('ALTER TABLE solicitud_gastos_revision DROP FOREIGN KEY FK_F9DBA68F1CB9D6E4');
        $this->addSql('ALTER TABLE solicitud_gastos_revision DROP FOREIGN KEY FK_F9DBA68F5D430949');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6495D430949');
        $this->addSql('DROP TABLE acciones');
        $this->addSql('DROP TABLE ciclo_indicadores');
        $this->addSql('DROP TABLE departamento');
        $this->addSql('DROP TABLE encabezado');
        $this->addSql('DROP TABLE grupo_indicadores_basicos');
        $this->addSql('DROP TABLE historial_acciones');
        $this->addSql('DROP TABLE historial_indicador_valor');
        $this->addSql('DROP TABLE indicadores');
        $this->addSql('DROP TABLE indicadores_basicos');
        $this->addSql('DROP TABLE departamento_indicadores_basicos');
        $this->addSql('DROP TABLE modulo_acceso');
        $this->addSql('DROP TABLE modulo_sistema');
        $this->addSql('DROP TABLE nombramiento');
        $this->addSql('DROP TABLE partidas_presupuestales');
        $this->addSql('DROP TABLE personal');
        $this->addSql('DROP TABLE proceso_clave');
        $this->addSql('DROP TABLE proceso_estrategico');
        $this->addSql('DROP TABLE puesto');
        $this->addSql('DROP TABLE reporte_indicador_actividad');
        $this->addSql('DROP TABLE reporte_indicador_evidencia');
        $this->addSql('DROP TABLE reporte_indicador_trimestre');
        $this->addSql('DROP TABLE reporte_indicadores');
        $this->addSql('DROP TABLE reporte_pta_accion');
        $this->addSql('DROP TABLE reporte_pta_accion_partida');
        $this->addSql('DROP TABLE reporte_pta_evidencias');
        $this->addSql('DROP TABLE reporte_pta_indicador');
        $this->addSql('DROP TABLE reporte_pta_trimestre');
        $this->addSql('DROP TABLE responsables');
        $this->addSql('DROP TABLE semaforo_indicadores');
        $this->addSql('DROP TABLE semaforo_indicadores_media');
        $this->addSql('DROP TABLE solicitud_gastos');
        $this->addSql('DROP TABLE solicitud_gastos_bancos');
        $this->addSql('DROP TABLE solicitud_gastos_comprobante');
        $this->addSql('DROP TABLE solicitud_gastos_configuracion');
        $this->addSql('DROP TABLE solicitud_gastos_evidencia');
        $this->addSql('DROP TABLE solicitud_gastos_folio_serie');
        $this->addSql('DROP TABLE solicitud_gastos_partida');
        $this->addSql('DROP TABLE solicitud_gastos_revision');
        $this->addSql('DROP TABLE tipo_solicitud');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
