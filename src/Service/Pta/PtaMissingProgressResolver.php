<?php

namespace App\Service\Pta;

use App\Entity\Acciones;
use App\Entity\Encabezado;
use App\Repository\EncabezadoRepository;

/**
 * =========================================================
 * PTA — Missing Progress Resolver
 * ---------------------------------------------------------
 * Responsabilidad única:
 *  - Detectar ACCIONES que deben reportar en un mes dado,
 *    pero NO tienen avance registrado para ese mes.
 *
 * Reglas:
 *  - El mes se evalúa SOLO si está incluido en Acciones::$periodo
 *  - Un avance "0" es válido (sí cuenta como registrado)
 *  - Se considera "sin avance" cuando:
 *      - valorAlcanzado es null
 *      - o no existe la clave del mes
 *      - o la clave existe pero su valor es null
 *
 * IMPORTANTE:
 *  - Este servicio NO envía correos
 *  - Este servicio NO decide qué día es (15/25/1)
 *  - Solo devuelve qué PTAs tienen acciones pendientes ese mes
 * =========================================================
 */
final class PtaMissingProgressResolver
{
    public function __construct(
        private readonly EncabezadoRepository $encabezadoRepository
    ) {}

    /**
     * Resuelve PTAs con acciones sin avance en el mes/año indicados.
     *
     * @param int $anio  Año de ejecución (Encabezado::$anioEjecucion)
     * @param int $mes   Mes numérico 1-12
     *
     * @return array<int, array{encabezado: Encabezado, acciones: Acciones[]}>
     *         - Un elemento por PTA (Encabezado)
     *         - Con lista de acciones pendientes en ese mes
     */
    public function resolve(int $anio, int $mes): array
    {
        // 1) Traer PTAs activos del año (idealmente con acciones y responsables cargados)
        $ptas = $this->encabezadoRepository->findBy([
            'status' => true,
            'anioEjecucion' => $anio,
        ]);

        // 2) Normalizar el mes a varias llaves posibles
        //    (porque tu BD usa arrays y puede venir como número, string, nombre, etc.)
        $monthKeys = $this->buildMonthKeys($mes);

        $result = [];

        // 3) Revisar cada PTA y sus acciones
        foreach ($ptas as $pta) {
            $accionesPendientes = [];

            foreach ($pta->getAcciones() as $accion) {
                if ($this->accionDebeEvaluarseEnMes($accion, $monthKeys) === false) {
                    continue; // ese mes no aplica para esa acción
                }

                if ($this->accionTieneAvanceEnMes($accion, $monthKeys) === false) {
                    $accionesPendientes[] = $accion;
                }
            }

            // 4) Si hay al menos una acción pendiente, este PTA entra al resultado
            if (!empty($accionesPendientes)) {
                $result[] = [
                    'encabezado' => $pta,
                    'acciones' => $accionesPendientes,
                ];
            }
        }

        return $result;
    }

    /**
     * Determina si una acción debe evaluarse en el mes indicado,
     * basándose en Acciones::$periodo (array).
     */
    private function accionDebeEvaluarseEnMes(Acciones $accion, array $monthKeys): bool
    {
        $periodo = $accion->getPeriodo(); // array (puede ser [1,2,3] o ["Enero", ...])

        // Si el periodo está vacío, por seguridad: NO evaluamos (evita falsos positivos)
        if (empty($periodo)) {
            return false;
        }

        // Convertimos todo a strings comparables (normalización simple)
        $periodoNorm = array_map(fn($v) => $this->normalizeKey($v), $periodo);

        foreach ($monthKeys as $key) {
            if (in_array($this->normalizeKey($key), $periodoNorm, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determina si una acción tiene avance registrado en el mes:
     * - Si valorAlcanzado es null -> NO hay avance
     * - Si existe clave del mes y su valor NO es null -> SÍ hay avance
     *   (incluye 0 como válido)
     */
    private function accionTieneAvanceEnMes(Acciones $accion, array $monthKeys): bool
    {
        $valor = $accion->getValorAlcanzado(); // ?array (JSON)

        if ($valor === null) {
            return false;
        }

        // Normalizamos llaves del JSON para comparar sin problemas
        $valorNorm = [];
        foreach ($valor as $k => $v) {
            $valorNorm[$this->normalizeKey($k)] = $v;
        }

        foreach ($monthKeys as $key) {
            $nk = $this->normalizeKey($key);

            if (!array_key_exists($nk, $valorNorm)) {
                continue;
            }

            // Si existe la clave y su valor NO es null, cuenta como registrado.
            // 0 es válido -> (0 !== null) => true
            if ($valorNorm[$nk] !== null) {
                return true;
            }
        }

        return false;
    }

    /**
     * Construye posibles llaves del mes para soportar varios formatos:
     * - 1, "1", "01"
     * - "enero", "Enero"
     * - "jan", "january" (por si acaso)
     *
     * Esto evita que el resolver dependa de un único formato.
     */
    private function buildMonthKeys(int $mes): array
    {
        $mes = max(1, min(12, $mes));

        $n = (string) $mes;          // "1"
        $n2 = str_pad($n, 2, '0', STR_PAD_LEFT); // "01"

        $es = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        $en = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];

        $enShort = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
        ];

        return [
            $mes, $n, $n2,
            $es[$mes], mb_strtolower($es[$mes]),
            $en[$mes], mb_strtolower($en[$mes]),
            $enShort[$mes], mb_strtolower($enShort[$mes]),
        ];
    }

    /**
     * Normaliza llaves para comparar:
     * - trim
     * - minúsculas
     * - quita dobles espacios
     */
    private function normalizeKey(mixed $value): string
    {
        $s = trim((string) $value);
        $s = preg_replace('/\s+/', ' ', $s);
        return mb_strtolower($s);
    }
}
