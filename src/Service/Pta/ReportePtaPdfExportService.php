<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ReportePtaPdfExportService
{
    /**
     * Orden:
     * 0 = Indicador institucional que impacta
     * 1 = Indicador PTA
     * 2 = Unidad de medida
     * 3 = Meta
     * 4 = Resultado
     * 5 = Medio de verificación
     * 6 = Meta cumplida / justificación
     * 7 = Responsable
     */
    private const WIDTHS = [1600, 1500, 1550, 880, 1250, 1400, 2200, 2450];

    public function __construct(
        private ReportePtaExportDataBuilderService $builder,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function exportar(Encabezado $encabezado, int $trimestre): BinaryFileResponse
    {
        $data = $this->builder->build($encabezado, $trimestre);

        $nombreArchivo = sprintf(
            'reporte_pta_%s_trimestre_%s_%s.pdf',
            $encabezado->getId(),
            $trimestre,
            uniqid()
        );

        $rutaTemporal = $this->parameterBag->get('kernel.project_dir') . '/var/tmp/' . $nombreArchivo;

        if (!is_dir(dirname($rutaTemporal))) {
            mkdir(dirname($rutaTemporal), 0777, true);
        }

        $tempDirMpdf = $this->parameterBag->get('kernel.project_dir') . '/var/tmp/mpdf';
        if (!is_dir($tempDirMpdf)) {
            mkdir($tempDirMpdf, 0777, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'Letter',
            'margin_top' => 28,
            'margin_bottom' => 22,
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_header' => 4,
            'margin_footer' => 4,
            'default_font' => 'notosans',
            'tempDir' => $tempDirMpdf,
        ]);

        $headerLogo = $this->obtenerRutaLogo('logo encabezado de pagina.png');
        $footerLogo = $this->obtenerRutaLogo('logo pie de pagina.png');

        $mpdf->SetHTMLHeader($this->construirHeaderHtml($headerLogo));
        $mpdf->SetHTMLFooter($this->construirFooterHtml($footerLogo));

        $html = $this->construirDocumentoHtml($data);

        $mpdf->WriteHTML($html);
        $mpdf->Output($rutaTemporal, Destination::FILE);

        $response = new BinaryFileResponse($rutaTemporal);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($rutaTemporal)
        );
        $response->deleteFileAfterSend(true);

        return $response;
    }

    private function construirDocumentoHtml(array $data): string
    {
        $html = '<html><head><meta charset="UTF-8">' . $this->construirCss() . '</head><body>';

        $html .= $this->construirTituloHtml($data);

        $indicadores = $data['indicadores'] ?? [];

        foreach ($indicadores as $index => $indicador) {
            if ($index > 0) {
                $html .= '<pagebreak />';
            }

            $html .= $this->construirTablaIndicadorHtml($data, $indicador);
        }

        $html .= '</body></html>';

        return $html;
    }

    private function construirCss(): string
{
    $porcentajes = $this->calcularPorcentajesColumnas();

    return '
    <style>
        body {
            font-family: notosans, sans-serif;
            font-size: 8pt;
            color: #000000;
        }

        .titulo-documento {
            text-align: center;
            font-family: sans-serif;
            font-size: 11pt;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.25;
            margin-bottom: 10pt;
        }

        table.reporte {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-family: notosans, sans-serif;
            font-size: 8pt;
            margin-bottom: 0;
        }

        table.reporte td,
        table.reporte th {
            border: 0.75pt solid #000000;
            vertical-align: middle;
            padding: 3pt;
            word-wrap: break-word;
        }

        .bg-blue {
            background: #215E99;
            color: #FFFFFF;
            font-weight: bold;
            text-align: center;
        }

        .bg-light {
            background: #DAE9F7;
            font-weight: bold;
            text-align: center;
        }

        .center {
            text-align: center;
            vertical-align: middle;
        }

        .bold {
            font-weight: bold;
        }

        .formula-acciones-texto {
            padding-top: 8pt;
            padding-bottom: 8pt;
            padding-left: 14pt;
            padding-right: 14pt;
            text-align: left;
            vertical-align: middle;
        }

        .evidencias-box {
            width: 100%;
            border: 0.75pt solid #000000;
            border-top: none;
            padding: 6pt 6pt 4pt 6pt;
            box-sizing: border-box;
        }

        .evidencias-titulo {
            text-align: center !important;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 4pt 0;
            width: 100%;
            display: block;
        }

        .evidencia-bloque {
            text-align: center;
            margin-top: 2pt;
            margin-bottom: 4pt;
            width: 100%;
        }

        .evidencia-descripcion {
            text-align: center !important;
            width: 100%;
            display: block;
            margin: 0 0 2pt 0;
            line-height: 1.25;
        }

        table.evidencia-outer {
            width: 100%;
            border-collapse: collapse;
            border: none;
            margin-top: 1pt;
            margin-bottom: 2pt;
        }

        table.evidencia-outer td {
            border: none !important;
            text-align: center;
            vertical-align: middle;
            padding: 0;
        }

        table.evidencia-inner {
            border-collapse: collapse;
            border: none;
            margin: 0 auto;
        }

        table.evidencia-inner td {
            border: none !important;
            text-align: center;
            vertical-align: middle;
            padding: 0 3pt;
        }

        table.evidencia-inner img {
            border: none;
            margin: 0;
            padding: 0;
        }

        .w0 { width: ' . $porcentajes[0] . '%; }
        .w1 { width: ' . $porcentajes[1] . '%; }
        .w2 { width: ' . $porcentajes[2] . '%; }
        .w3 { width: ' . $porcentajes[3] . '%; }
        .w4 { width: ' . $porcentajes[4] . '%; }
        .w5 { width: ' . $porcentajes[5] . '%; }
        .w6 { width: ' . $porcentajes[6] . '%; }
        .w7 { width: ' . $porcentajes[7] . '%; }
    </style>';
}

    private function construirTituloHtml(array $data): string
    {
        $anio = mb_strtoupper((string)($data['anio'] ?? ''), 'UTF-8');
        $periodo = mb_strtoupper((string)($data['trimestre_label'] ?? ''), 'UTF-8');

        $puesto = '';

        foreach (($data['indicadores'] ?? []) as $indicador) {
            $puestoActual = trim((string)($indicador['responsable_puesto'] ?? ''));

            if ($puestoActual !== '') {
                $puesto = $puestoActual;
                break;
            }
        }

        $puesto = mb_strtoupper($puesto, 'UTF-8');

        return '
            <div class="titulo-documento">
                REPORTE TRIMESTRAL DE ACTIVIDADES DEL PTA ' . $this->esc($anio) . '<br>
                PERIODO ' . $this->esc($periodo) . ' ' . $this->esc($anio) . '<br>
                ' . $this->esc($puesto) . '
            </div>
        ';
    }

    private function construirTablaIndicadorHtml(array $data, array $indicador): string
{
    $headers = $this->construirHeaders($data);
    $resultado = $this->normalizarValor($indicador['resultado'] ?? '');

    $html = '<table class="reporte">';

    $html .= '
        <tr>
            <td colspan="8" class="bg-blue">
                Objetivo: ' . $this->esc((string)($data['pta']['objetivo'] ?? '')) . '
            </td>
        </tr>
    ';

    $html .= '
        <tr>
            <td colspan="8" class="bg-blue">
                Proyecto ' . $this->esc((string)($data['pta']['id'] ?? '')) . '. ' . $this->esc((string)($data['pta']['nombre'] ?? '')) . '
            </td>
        </tr>
    ';

    $html .= '<tr>';
    foreach ($headers as $i => $lineasHeader) {
        $html .= '<td class="bg-light center w' . $i . '">' .
            implode('<br>', array_map(fn($l) => $this->esc($l), $lineasHeader)) .
            '</td>';
    }
    $html .= '</tr>';

    $html .= '<tr>';
    $html .= '<td class="center w0">' . $this->esc((string)($indicador['indicador_basico'] ?? '')) . '</td>';
    $html .= '<td class="center w1">' . $this->esc((string)($indicador['indicador_pta'] ?? '')) . '</td>';
    $html .= '<td class="center w2">' . $this->esc((string)($indicador['unidad_medida'] ?? '')) . '</td>';
    $html .= '<td class="center w3">' . $this->esc($this->normalizarValor($indicador['meta'] ?? '')) . '</td>';
    $html .= '<td class="center bold w4">' . $this->esc($resultado) . '</td>';
    $html .= '<td class="center w5">' . $this->esc((string)($indicador['medio_verificacion'] ?? '')) . '</td>';
    $html .= '<td class="center w6">' . $this->esc((string)($indicador['meta_cumplida'] ?? '')) . '</td>';
    $html .= '<td class="center w7">' . $this->esc((string)($indicador['responsable_puesto'] ?? '')) . '</td>';
    $html .= '</tr>';

    $html .= '
        <tr>
            <td colspan="2" class="bg-light center">Formula</td>
            <td colspan="2" class="center">' . $this->esc((string)($indicador['formula_empleada'] ?? '')) . '</td>
            <td colspan="4" class="formula-acciones-texto">' . $this->esc((string)($indicador['formula_descripcion'] ?? '')) . '</td>
        </tr>
    ';

    $acciones = $indicador['acciones'] ?? [];

    if (empty($acciones)) {
        $html .= '
            <tr>
                <td colspan="2" class="bg-light center">Acción</td>
                <td colspan="2" class="center"></td>
                <td colspan="4" class="formula-acciones-texto"></td>
            </tr>
        ';
    } else {
        foreach ($acciones as $i => $accion) {
            $html .= '
                <tr>
                    <td colspan="2" class="bg-light center">Acción ' . ($i + 1) . '</td>
                    <td colspan="2" class="center"></td>
                    <td colspan="4" class="formula-acciones-texto">' . $this->esc((string)($accion['descripcion'] ?? '')) . '</td>
                </tr>
            ';
        }
    }

    $html .= '</table>';

    $html .= $this->construirBloqueEvidenciasHtml($indicador);

    return $html;
}

private function construirBloqueEvidenciasHtml(array $indicador): string
{
    $html = '<div class="evidencias-box">';
    $html .= '<div class="evidencias-titulo">EVIDENCIAS</div>';

    $evidencias = $indicador['evidencias'] ?? [];

    foreach ($evidencias as $evidencia) {
        $descripcion = trim((string)($evidencia['descripcion'] ?? ''));

        $html .= '<div class="evidencia-bloque">';

        if ($descripcion !== '') {
            $html .= '<div class="evidencia-descripcion">' . $this->esc($descripcion) . '</div>';
        }

        $imagenesValidas = array_values(array_filter(
            $evidencia['imagenes'] ?? [],
            fn($img) => !empty($img['exists']) && !empty($img['path']) && is_file($img['path'])
        ));

        if (!empty($imagenesValidas)) {
            $html .= $this->construirHtmlImagenesEvidencia($imagenesValidas);
        }

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}

    private function construirHtmlImagenesEvidencia(array $imagenesValidas): string
    {
        $count = count($imagenesValidas);

        if ($count === 1) {
            [$width, $height] = $this->calcularTamanoImagenPdf((string)$imagenesValidas[0]['path'], 340, 240);
            $innerWidth = $width;

            return '
                <table class="evidencia-outer">
                    <tr>
                        <td>
                            <table class="evidencia-inner" style="width:' . $innerWidth . 'px;">
                                <tr>
                                    <td>
                                        <img src="' . $this->esc((string)$imagenesValidas[0]['path']) . '" width="' . $width . '" height="' . $height . '">
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            ';
        }

        if ($count === 2) {
            $gap = 10;
            $items = [];

            foreach ($imagenesValidas as $imagen) {
                $items[] = $this->calcularTamanoImagenPdf((string)$imagen['path'], 230, 165);
            }

            $innerWidth = $items[0][0] + $items[1][0] + $gap;

            $html = '
                <table class="evidencia-outer">
                    <tr>
                        <td>
                            <table class="evidencia-inner" style="width:' . $innerWidth . 'px;">
                                <tr>
            ';

            foreach ($imagenesValidas as $index => $imagen) {
                [$width, $height] = $items[$index];
                $html .= '<td><img src="' . $this->esc((string)$imagen['path']) . '" width="' . $width . '" height="' . $height . '"></td>';
            }

            $html .= '
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            ';

            return $html;
        }

        if ($count === 3) {
            $gap = 8;
            $items = [];

            foreach ($imagenesValidas as $imagen) {
                $items[] = $this->calcularTamanoImagenPdf((string)$imagen['path'], 150, 110);
            }

            $innerWidth = $items[0][0] + $items[1][0] + $items[2][0] + ($gap * 2);

            $html = '
                <table class="evidencia-outer">
                    <tr>
                        <td>
                            <table class="evidencia-inner" style="width:' . $innerWidth . 'px;">
                                <tr>
            ';

            foreach ($imagenesValidas as $index => $imagen) {
                [$width, $height] = $items[$index];
                $html .= '<td><img src="' . $this->esc((string)$imagen['path']) . '" width="' . $width . '" height="' . $height . '"></td>';
            }

            $html .= '
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            ';

            return $html;
        }

        $gap = 10;
        $primeraFila = array_slice($imagenesValidas, 0, 2);
        $segundaFila = array_slice($imagenesValidas, 2, 2);

        $fila1Sizes = [];
        foreach ($primeraFila as $imagen) {
            $fila1Sizes[] = $this->calcularTamanoImagenPdf((string)$imagen['path'], 180, 130);
        }

        $fila2Sizes = [];
        foreach ($segundaFila as $imagen) {
            $fila2Sizes[] = $this->calcularTamanoImagenPdf((string)$imagen['path'], 180, 130);
        }

        $innerWidth = 0;
        if (count($fila1Sizes) === 2) {
            $innerWidth = $fila1Sizes[0][0] + $fila1Sizes[1][0] + $gap;
        } elseif (count($fila1Sizes) === 1) {
            $innerWidth = $fila1Sizes[0][0];
        }

        $html = '
            <table class="evidencia-outer">
                <tr>
                    <td>
                        <table class="evidencia-inner" style="width:' . $innerWidth . 'px;">
                            <tr>
        ';

        foreach ($primeraFila as $index => $imagen) {
            [$width, $height] = $fila1Sizes[$index];
            $html .= '<td><img src="' . $this->esc((string)$imagen['path']) . '" width="' . $width . '" height="' . $height . '"></td>';
        }

        $html .= '</tr>';

        if (!empty($segundaFila)) {
            $html .= '<tr>';
            foreach ($segundaFila as $index => $imagen) {
                [$width, $height] = $fila2Sizes[$index];
                $html .= '<td><img src="' . $this->esc((string)$imagen['path']) . '" width="' . $width . '" height="' . $height . '"></td>';
            }
            $html .= '</tr>';
        }

        $html .= '
                        </table>
                    </td>
                </tr>
            </table>
        ';

        return $html;
    }

    private function construirHeaderHtml(?string $logoPath): string
    {
        if (!$logoPath || !is_file($logoPath)) {
            return '';
        }

        return '
            <div style="text-align:center;">
                <img src="' . $this->esc($logoPath) . '" style="width:500px; height:auto;">
            </div>
        ';
    }

    private function construirFooterHtml(?string $logoPath): string
    {
        if (!$logoPath || !is_file($logoPath)) {
            return '';
        }

        return '
            <div style="text-align:center;">
                <img src="' . $this->esc($logoPath) . '" style="width:900px; height:auto;">
            </div>
        ';
    }

    private function calcularPorcentajesColumnas(): array
    {
        $total = array_sum(self::WIDTHS);

        return array_map(
            fn(int $width) => round(($width / $total) * 100, 4),
            self::WIDTHS
        );
    }

    private function construirHeaders(array $data): array
    {
        $anio = isset($data['anio']) && is_numeric($data['anio']) ? (int)$data['anio'] : null;

        $metaHeader = $anio !== null
            ? ['Meta ' . $anio]
            : ['Meta'];

        return [
            ['Indicador', 'Institucional', 'que impacta'],
            ['Indicador', 'PTA'],
            ['Unidad', 'de medida'],
            $metaHeader,
            $this->construirHeaderResultado($data),
            ['Medio de', 'verificación'],
            ['Meta cumplida o', 'justificación por no', 'cumplir la meta al', 'término del ejercicio'],
            ['Responsable'],
        ];
    }

    private function construirHeaderResultado(array $data): array
    {
        $resultadoLabel = trim((string)($data['resultado_label'] ?? ''));
        $trimestreLabel = trim((string)($data['trimestre_label'] ?? ''));

        if ($resultadoLabel === '' && $trimestreLabel === '') {
            return ['Resultado', 'del trimestre'];
        }

        if ($resultadoLabel !== '' && preg_match('/^Resultado\s+(.*)$/iu', $resultadoLabel, $matches)) {
            $resto = trim($matches[1]);

            if ($resto !== '') {
                return array_merge(['Resultado'], preg_split('/\s+/u', $resto));
            }
        }

        if ($trimestreLabel !== '') {
            return ['Resultado', 'a', $trimestreLabel];
        }

        return ['Resultado'];
    }

    private function calcularTamanoImagenPdf(string $path, int $maxWidth, int $maxHeight): array
    {
        $info = @getimagesize($path);

        if (!$info || empty($info[0]) || empty($info[1])) {
            return [$maxWidth, $maxHeight];
        }

        $originalWidth = $info[0];
        $originalHeight = $info[1];

        $ratio = min(
            $maxWidth / $originalWidth,
            $maxHeight / $originalHeight
        );

        $ratio = min($ratio, 1);

        return [
            (int) round($originalWidth * $ratio),
            (int) round($originalHeight * $ratio),
        ];
    }

    private function normalizarValor(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        if (is_numeric($valor)) {
            return number_format((float)$valor, 2, '.', '');
        }

        return (string)$valor;
    }

    private function obtenerRutaLogo(string|array $nombres): ?string
    {
        $nombres = is_array($nombres) ? $nombres : [$nombres];

        $basePath = $this->parameterBag->get('kernel.project_dir') . '/public/uploads/logos/';

        foreach ($nombres as $nombre) {
            $ruta = $basePath . $nombre;

            if (is_file($ruta)) {
                return $ruta;
            }
        }

        return null;
    }

    private function esc(string $texto): string
    {
        return htmlspecialchars($texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}