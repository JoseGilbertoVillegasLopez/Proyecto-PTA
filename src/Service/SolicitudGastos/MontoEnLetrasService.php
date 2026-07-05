<?php

namespace App\Service\SolicitudGastos;

class MontoEnLetrasService
{
    private const UNIDADES = ['', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve'];

    private const ESPECIALES_10_20 = [
        10 => 'diez', 11 => 'once', 12 => 'doce', 13 => 'trece', 14 => 'catorce', 15 => 'quince',
        16 => 'dieciséis', 17 => 'diecisiete', 18 => 'dieciocho', 19 => 'diecinueve', 20 => 'veinte',
    ];

    private const VEINTIS = [
        1 => 'veintiuno', 2 => 'veintidós', 3 => 'veintitrés', 4 => 'veinticuatro', 5 => 'veinticinco',
        6 => 'veintiséis', 7 => 'veintisiete', 8 => 'veintiocho', 9 => 'veintinueve',
    ];

    private const DECENAS = [30 => 'treinta', 40 => 'cuarenta', 50 => 'cincuenta', 60 => 'sesenta', 70 => 'setenta', 80 => 'ochenta', 90 => 'noventa'];

    private const CENTENAS = [100 => 'ciento', 200 => 'doscientos', 300 => 'trescientos', 400 => 'cuatrocientos', 500 => 'quinientos', 600 => 'seiscientos', 700 => 'setecientos', 800 => 'ochocientos', 900 => 'novecientos'];

    public function convertir(string|float $monto): string
    {
        $monto = abs((float) $monto);
        $entero = (int) floor($monto);
        $centavos = (int) round(($monto - $entero) * 100);

        if ($centavos === 100) {
            $entero++;
            $centavos = 0;
        }

        $texto = $this->aplicarApocope($this->numeroATexto($entero));
        $centavosStr = str_pad((string) $centavos, 2, '0', STR_PAD_LEFT);

        return sprintf('%s PESOS %s/100 M.N.', mb_strtoupper($texto), $centavosStr);
    }

    private function numeroATexto(int $numero): string
    {
        if ($numero === 0) {
            return 'cero';
        }

        $millones = intdiv($numero, 1000000);
        $resto = $numero % 1000000;
        $miles = intdiv($resto, 1000);
        $unidades = $resto % 1000;

        $partes = [];

        if ($millones > 0) {
            $partes[] = $millones === 1 ? 'un millón' : $this->convertirGrupo($millones) . ' millones';
        }

        if ($miles > 0) {
            $partes[] = $miles === 1 ? 'mil' : $this->aplicarApocope($this->convertirGrupo($miles)) . ' mil';
        }

        if ($unidades > 0) {
            $partes[] = $this->convertirGrupo($unidades);
        }

        return implode(' ', $partes);
    }

    private function convertirGrupo(int $numero): string
    {
        if ($numero < 10) {
            return self::UNIDADES[$numero];
        }

        if ($numero <= 20) {
            return self::ESPECIALES_10_20[$numero];
        }

        if ($numero < 30) {
            return self::VEINTIS[$numero - 20];
        }

        if ($numero < 100) {
            $decena = intdiv($numero, 10) * 10;
            $unidad = $numero % 10;

            return $unidad === 0 ? self::DECENAS[$decena] : self::DECENAS[$decena] . ' y ' . self::UNIDADES[$unidad];
        }

        if ($numero === 100) {
            return 'cien';
        }

        $centena = intdiv($numero, 100) * 100;
        $resto = $numero % 100;

        return $resto === 0 ? self::CENTENAS[$centena] : self::CENTENAS[$centena] . ' ' . $this->convertirGrupo($resto);
    }

    private function aplicarApocope(string $texto): string
    {
        if ($texto === 'uno') {
            return 'un';
        }

        if (str_ends_with($texto, 'veintiuno')) {
            return substr($texto, 0, -strlen('veintiuno')) . 'veintiún';
        }

        if (str_ends_with($texto, ' uno')) {
            return substr($texto, 0, -3) . 'un';
        }

        return $texto;
    }
}
