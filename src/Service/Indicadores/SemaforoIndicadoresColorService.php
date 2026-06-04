<?php

namespace App\Service\Indicadores;

use App\Entity\IndicadoresBasicos;
use App\Entity\SemaforoIndicadores;
use App\Entity\SemaforoIndicadoresMedia;

class SemaforoIndicadoresColorService
{
    private const INDICADORES_INVERSOS = [
        'DESERCION',
        'REPROBACION',
        'BAJA TEMPORAL',
        'NO DE ALUMNOS POR COMPUTADORA',
        'COSTO POR ALUMNO',
    ];

    public function getColor(
        IndicadoresBasicos $indicador,
        ?SemaforoIndicadores $valor,
        ?SemaforoIndicadoresMedia $media
    ): ?string {
        if (!$valor || !$media) {
            return null;
        }

        $resultado = $this->toFloat($valor->getResultadoCiclo());
        $mediaEstatal = $this->toFloat($media->getMediaEstatal());
        $mediaNacional = $this->toFloat($media->getMediaNacional());

        if (
            $resultado === null
            && $this->toFloat($valor->getCantidad1()) === 0.0
            && $this->toFloat($valor->getCantidad2()) === 0.0
        ) {
            $resultado = 0.0;
        }

        if ($resultado === null || $mediaEstatal === null || $mediaNacional === null) {
            return null;
        }

        $limiteInferior = min($mediaEstatal, $mediaNacional);
        $limiteSuperior = max($mediaEstatal, $mediaNacional);
        $esInverso = $this->isIndicadorInverso($indicador);

        if ($resultado < $limiteInferior) {
            return $esInverso ? 'verde' : 'rojo';
        }

        if ($resultado > $limiteSuperior) {
            return $esInverso ? 'rojo' : 'verde';
        }

        return 'amarillo';
    }

    /**
     * @param array<int, array<int, SemaforoIndicadores>> $valores
     * @param array<int, SemaforoIndicadoresMedia> $medias
     * @param IndicadoresBasicos[] $indicadores
     */
    public function buildIndexedColors(array $indicadores, array $valores, array $medias): array
    {
        $colors = [];

        foreach ($indicadores as $indicador) {
            $indicadorId = $indicador->getId();

            if (!$indicadorId) {
                continue;
            }

            foreach ($valores[$indicadorId] ?? [] as $cicloId => $valor) {
                $color = $this->getColor($indicador, $valor, $medias[$indicadorId] ?? null);

                if ($color) {
                    $colors[$indicadorId][$cicloId] = $color;
                }
            }
        }

        return $colors;
    }

    private function isIndicadorInverso(IndicadoresBasicos $indicador): bool
    {
        $nombre = $this->normalizeName($indicador->getNombreIndicador() ?? '');

        return in_array($nombre, self::INDICADORES_INVERSOS, true);
    }

    private function normalizeName(string $value): string
    {
        $value = trim($value);
        $value = strtr($value, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
            'á' => 'A',
            'é' => 'E',
            'í' => 'I',
            'ó' => 'O',
            'ú' => 'U',
            'ü' => 'U',
            'ñ' => 'N',
        ]);
        $value = strtoupper($value);
        $value = preg_replace('/[^A-Z0-9]+/', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function toFloat(?string $value): ?float
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
