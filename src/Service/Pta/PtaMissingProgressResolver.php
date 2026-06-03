<?php

namespace App\Service\Pta;

use App\Entity\Acciones;
use App\Entity\Encabezado;
use App\Entity\Indicadores;
use App\Repository\EncabezadoRepository;

/**
 * =========================================================
 * PtaMissingProgressResolver
 * ---------------------------------------------------------
 * Detecta PTAs que tienen avances pendientes de captura
 * en un mes/año dado. Usado para enviar notificaciones
 * recordatorias al responsable.
 *
 * NUEVO MODELO — dos tipos de pendiente:
 *
 * 1. ACCIÓN SIN MARCAR:
 *    Un mes del periodo de una acción ya pasó y no tiene
 *    ningún registro en HistorialAcciones para ese mes.
 *    (antes se revisaba valorAlcanzado; ahora mesesCumplidos)
 *
 * 2. INDICADOR SIN VALOR:
 *    Un mes reportable del indicador ya pasó y no tiene
 *    valor registrado en Indicadores::$valorMensual.
 *
 * IMPORTANTE: este servicio NO envía correos ni decide
 * qué día es. Solo devuelve qué PTAs tienen pendientes.
 * =========================================================
 */
final class PtaMissingProgressResolver
{
    public function __construct(
        private readonly EncabezadoRepository $encabezadoRepository
    ) {}

    /**
     * Devuelve los PTAs con acciones sin marcar O indicadores sin valor
     * en el mes indicado.
     *
     * @param int $anio Año de ejecución (Encabezado::$anioEjecucion)
     * @param int $mes  Mes numérico 1-12
     *
     * @return array<int, array{
     *   encabezado:       Encabezado,
     *   acciones_pendientes: Acciones[],
     *   indicadores_pendientes: Indicadores[]
     * }>
     */
    public function resolve(int $anio, int $mes): array
    {
        $ptas = $this->encabezadoRepository->findBy([
            'status'         => true,
            'anioEjecucion'  => $anio,
        ]);

        $mesNombre = $this->mesANombre($mes);
        $result    = [];

        foreach ($ptas as $pta) {

            $accionesPendientes    = $this->resolverAccionesPendientes($pta, $mes, $mesNombre);
            $indicadoresPendientes = $this->resolverIndicadoresPendientes($pta, $mes, $mesNombre);

            // Solo incluir el PTA si tiene al menos un pendiente
            if (!empty($accionesPendientes) || !empty($indicadoresPendientes)) {
                $result[] = [
                    'encabezado'              => $pta,
                    'acciones_pendientes'     => $accionesPendientes,
                    'indicadores_pendientes'  => $indicadoresPendientes,
                ];
            }
        }

        return $result;
    }

    /**
     * Determina qué acciones del PTA no tienen registro de cumplimiento
     * para el mes dado.
     *
     * Una acción está pendiente cuando:
     *   - El mes está en su periodo de ejecución
     *   - No hay ninguna entrada en HistorialAcciones para ese mes
     *     (independientemente de si fue ✓ o ✗)
     *
     * @return Acciones[]
     */
    private function resolverAccionesPendientes(Encabezado $pta, int $mesNumero, string $mesNombre): array
    {
        $pendientes = [];

        foreach ($pta->getAcciones() as $accion) {

            // El mes debe estar en el periodo de la acción
            if (!in_array($mesNombre, $accion->getPeriodo(), true)) {
                continue;
            }

            // Revisar si existe algún registro en el historial para este mes
            $tieneRegistro = false;
            foreach ($accion->getHistorialAcciones() as $h) {
                if ($h->getMes() === $mesNumero) {
                    $tieneRegistro = true;
                    break;
                }
            }

            if (!$tieneRegistro) {
                $pendientes[] = $accion;
            }
        }

        return $pendientes;
    }

    /**
     * Determina qué indicadores del PTA no tienen valor snapshot
     * registrado para el mes dado.
     *
     * Un indicador está pendiente cuando:
     *   - El mes es reportable para ese indicador
     *     (está en el periodo de al menos una de sus acciones)
     *   - No hay valor en Indicadores::$valorMensual para ese mes
     *
     * @return Indicadores[]
     */
    private function resolverIndicadoresPendientes(Encabezado $pta, int $mesNumero, string $mesNombre): array
    {
        $pendientes = [];

        foreach ($pta->getIndicadores() as $indicador) {

            // Calcular si el mes es reportable para este indicador
            $esReportable = false;
            foreach ($pta->getAcciones() as $accion) {
                if ($accion->getIndicador() === $indicador->getIndice()
                    && in_array($mesNombre, $accion->getPeriodo(), true)) {
                    $esReportable = true;
                    break;
                }
            }

            if (!$esReportable) {
                continue;
            }

            // Revisar si ya tiene valor registrado para este mes
            $valorMensual = $indicador->getValorMensual() ?? [];
            $tieneValor   = isset($valorMensual[$mesNombre]) && $valorMensual[$mesNombre] !== null;

            if (!$tieneValor) {
                $pendientes[] = $indicador;
            }
        }

        return $pendientes;
    }

    /**
     * Convierte un número de mes (1-12) a su nombre en español.
     * Devuelve cadena vacía si el número es inválido.
     */
    private function mesANombre(int $mes): string
    {
        $meses = [
            1  => 'Enero',     2  => 'Febrero',   3  => 'Marzo',
            4  => 'Abril',     5  => 'Mayo',       6  => 'Junio',
            7  => 'Julio',     8  => 'Agosto',     9  => 'Septiembre',
            10 => 'Octubre',   11 => 'Noviembre',  12 => 'Diciembre',
        ];

        return $meses[$mes] ?? '';
    }
}
