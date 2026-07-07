<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260705120000 extends AbstractMigration
{
    private const BANCOS = [
        'CIBanco',
        'BanBase',
        'STP',
        'Mercado Pago',
        'BanCoppel',
        'Banco Multiva',
        'Interbanco',
        'Banco Famsa',
    ];

    public function getDescription(): string
    {
        return 'Agrega bancos faltantes del catalogo de finanzas (DRF)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::BANCOS as $nombre) {
            $this->addSql(
                "INSERT INTO solicitud_gastos_bancos (nombre, estado) VALUES (:nombre, 'activo')",
                ['nombre' => $nombre]
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::BANCOS as $nombre) {
            $this->addSql(
                'DELETE FROM solicitud_gastos_bancos WHERE nombre = :nombre',
                ['nombre' => $nombre]
            );
        }
    }
}
