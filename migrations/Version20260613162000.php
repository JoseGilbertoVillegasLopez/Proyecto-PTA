<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613162000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed inicial de bancos del beneficiario';
    }

    public function up(Schema $schema): void
    {
        $bancos = [
            'BBVA Bancomer',
            'Citibanamex',
            'Santander',
            'HSBC',
            'Banorte',
            'Scotiabank',
            'Inbursa',
            'BanBajío',
            'Afirme',
            'Mifel',
            'Banbajío',
            'Banco del Ejército (Banjército)',
            'Banco Azteca',
            'Hey Banco (Banregio)',
        ];

        foreach ($bancos as $nombre) {
            $this->addSql(
                "INSERT INTO solicitud_gastos_bancos (nombre, estado) VALUES (:nombre, 'activo')",
                ['nombre' => $nombre]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM solicitud_gastos_bancos WHERE estado = 'activo'");
    }
}
