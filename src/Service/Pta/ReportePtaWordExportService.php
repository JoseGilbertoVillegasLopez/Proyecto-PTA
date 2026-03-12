<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ReportePtaWordExportService
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
     *
     * IMPORTANTE:
     * Para que Fórmula/Acciones funcionen en UNA SOLA TABLA:
     * - columna 1 = widths[0] + widths[1]
     * - columna 2 = widths[2] + widths[3]
     * - columna 3 = el resto
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

        $phpWord = new PhpWord();

        $this->configurarDocumento($phpWord);
        $this->registrarEstilos($phpWord);

        $section = $phpWord->addSection([
            'paperSize'    => 'Letter',
            'marginTop'    => 567,
            'marginBottom' => 567,
            'marginLeft'   => 567,
            'marginRight'  => 567,
            'footerHeight' => 120,
        ]);

$this->agregarEncabezadoPagina($section);
$this->agregarPiePagina($section);

$this->agregarTituloDocumento($section, $data);


        $indicadores = $data['indicadores'] ?? [];

        foreach ($indicadores as $index => $indicador) {
            if ($index > 0) {
                $section->addPageBreak();
            }

            $this->agregarBloqueIndicador($section, $data, $indicador);
        }

        $nombreArchivo = sprintf(
            'reporte_pta_%s_trimestre_%s.docx',
            $encabezado->getId(),
            $trimestre
        );

        $rutaTemporal = $this->parameterBag->get('kernel.project_dir')
            . '/var/tmp/' . $nombreArchivo;

        if (!is_dir(dirname($rutaTemporal))) {
            mkdir(dirname($rutaTemporal), 0777, true);
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($rutaTemporal);

        $response = new BinaryFileResponse($rutaTemporal);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $nombreArchivo
        );
        $response->deleteFileAfterSend(true);

        return $response;
    }

    private function configurarDocumento(PhpWord $phpWord): void
    {
        $phpWord->getCompatibility()->setOoxmlVersion(15);
        $phpWord->getSettings()->setThemeFontLang(
            new \PhpOffice\PhpWord\Style\Language('es-MX')
        );

        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(8);
    }

    private function registrarEstilos(PhpWord $phpWord): void
    {
        $phpWord->addFontStyle('rpta-title', [
            'name' => 'Arial',
            'size' => 8,
            'bold' => true,
        ]);

        $phpWord->addFontStyle('rpta-normal', [
            'name' => 'Arial',
            'size' => 8,
        ]);

        $phpWord->addFontStyle('rpta-bold', [
            'name' => 'Arial',
            'size' => 8,
            'bold' => true,
        ]);

        $phpWord->addFontStyle('rpta-header-dark', [
            'name'  => 'Arial',
            'size'  => 8,
            'bold'  => true,
            'color' => '000000',
        ]);

        $phpWord->addFontStyle('rpta-header-white', [
            'name'  => 'Arial',
            'size'  => 8,
            'bold'  => true,
            'color' => 'FFFFFF',
        ]);

        $phpWord->addTableStyle('rpta-main-table', [
            'borderSize'         => 6,
            'borderColor'        => '000000',
            'borderInsideHSize'  => 6,
            'borderInsideHColor' => '000000',
            'borderInsideVSize'  => 6,
            'borderInsideVColor' => '000000',
            'cellMargin'         => 0,
            'cellSpacing'        => 6,
            'alignment'          => JcTable::CENTER,
            'layout'             => 'fixed',
            'width'              => 100 * 50,
            'unit'               => 'pct',
        ]);

        $phpWord->addFontStyle('rpta-main-title', [
            'name' => 'Aptos (Body)',
            'size' => 11,
            'bold' => true,
        ]);
    }

    private function agregarTituloDocumento($section, array $data): void
    {
        $anio = strtoupper((string)($data['anio'] ?? ''));
        $periodo = strtoupper((string)($data['trimestre_label'] ?? ''));
        $puesto = strtoupper((string)($data['pta']['responsable_puesto'] ?? ''));

        $titulo1 = 'REPORTE TRIMESTRAL DE ACTIVIDADES DEL PTA ' . $anio;
        $titulo2 = 'PERIODO ' . $periodo . ' ' . $anio;
        $titulo3 = $puesto;

        $section->addText($titulo1, 'rpta-main-title', [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 0,
        ]);

        $section->addText($titulo2, 'rpta-main-title', [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 0,
        ]);

        $section->addText($titulo3, 'rpta-main-title', [
            'alignment' => Jc::CENTER,
            'spaceAfter' => 0,
        ]);

        $section->addTextBreak(1);
    }


private function agregarEncabezadoPagina($section): void
{
    $header = $section->addHeader();

    $logoHeader = $this->obtenerRutaLogo('logo encabezado de pagina.png');

    $this->agregarImagenDirectaSiExiste($header, $logoHeader, 500, 100, Jc::CENTER);
}

private function agregarPiePagina($section): void
{
    $footer = $section->addFooter();

    $logoFooter = $this->obtenerRutaLogo('logo pie de pagina.png');

    $this->agregarImagenDirectaSiExiste($footer, $logoFooter, 900, 90, Jc::CENTER);
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

private function agregarImagenDirectaSiExiste(
    $contenedor,
    ?string $path,
    int $maxWidth,
    int $maxHeight,
    string $alignment = Jc::CENTER
): void {
    if (!$path || !is_file($path)) {
        return;
    }

    $info = @getimagesize($path);

    if (!$info || empty($info[0]) || empty($info[1])) {
        $width = $maxWidth;
        $height = $maxHeight;
    } else {
        $originalWidth = $info[0];
        $originalHeight = $info[1];

        $ratio = min(
            $maxWidth / $originalWidth,
            $maxHeight / $originalHeight
        );

        $width = (int) round($originalWidth * $ratio);
        $height = (int) round($originalHeight * $ratio);
    }

    $contenedor->addImage($path, [
        'width'     => $width,
        'height'    => $height,
        'alignment' => $alignment,
    ]);
}

    private function agregarBloqueIndicador($section, array $data, array $indicador): void
    {
        $table = $section->addTable('rpta-main-table');

        $widths = self::WIDTHS;
        $anchoTotal = array_sum($widths);

        $anchoBloqueIzquierdo = $widths[0] + $widths[1];
        $anchoBloqueCentral   = $widths[2] + $widths[3];
        $anchoBloqueDerecho   = $anchoTotal - $anchoBloqueIzquierdo - $anchoBloqueCentral;

        $headers = $this->construirHeaders($data);

        // =====================================================
        // OBJETIVO
        // =====================================================
        $table->addRow();
        $cell = $table->addCell($anchoTotal, array_merge(
            $this->estiloCelda(0, 0, 8, 8, '215E99'),
            [
                'gridSpan' => 8,
                'valign'   => 'center',
            ]
        ));
        $cell->addText(
            'Objetivo: ' . (string)($data['pta']['objetivo'] ?? ''),
            'rpta-header-white',
            [
                'alignment'   => Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
            ]
        );

        // =====================================================
        // PROYECTO
        // =====================================================
        $table->addRow();
        $cell = $table->addCell($anchoTotal, array_merge(
            $this->estiloCelda(0, 0, 8, 8, '215E99'),
            [
                'gridSpan' => 8,
                'valign'   => 'center',
            ]
        ));
        $cell->addText(
            'Proyecto ' . ($data['pta']['id'] ?? '') . '. ' . (string)($data['pta']['nombre'] ?? ''),
            'rpta-header-white',
            [
                'alignment'   => Jc::CENTER,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
            ]
        );

        // =====================================================
        // HEADERS
        // =====================================================
        $table->addRow();

        foreach ($headers as $i => $lineasHeader) {
            $cell = $table->addCell(
                $widths[$i],
                $this->estiloCelda(6, 6, 6, 6, 'DAE9F7')
            );

            $this->agregarTextoMultilineaCentrado($cell, $lineasHeader, 'rpta-header-dark');
        }

        // =====================================================
        // DATOS
        // =====================================================
        $table->addRow();

        $values = [
            $indicador['indicador_basico'] ?? '',
            $indicador['indicador_pta'] ?? '',
            $indicador['unidad_medida'] ?? '',
            $this->normalizarValor($indicador['meta'] ?? ''),
            $this->normalizarValor($indicador['resultado'] ?? ''),
            $indicador['medio_verificacion'] ?? '',
            $indicador['meta_cumplida'] ?? '',
            $indicador['responsable_puesto'] ?? '',
        ];

        foreach ($values as $i => $value) {
            $alignment = in_array($i, [5, 6], true) ? Jc::CENTER : Jc::CENTER;

            $cell = $table->addCell(
                $widths[$i],
                $this->estiloCelda(8, 8, 8, 8)
            );

            $cell->addText((string)$value, 'rpta-normal', [
                'alignment' => $alignment,
            ]);
        }

        // =====================================================
        // FÓRMULA
        // =====================================================
        $table->addRow(320);

        $cell1 = $table->addCell($anchoBloqueIzquierdo, array_merge(
            $this->estiloCelda(6, 6, 6, 6, 'DAE9F7'),
            [
                'gridSpan' => 2,
                'valign'   => 'center',
            ]
        ));
        $cell1->addText('Formula', 'rpta-header-dark', [
            'alignment' => Jc::CENTER,
        ]);

        $cell2 = $table->addCell($anchoBloqueCentral, array_merge(
            $this->estiloCelda(6, 6, 6, 6),
            ['gridSpan' => 2]
        ));
        $cell2->addText((string)($indicador['formula_empleada'] ?? ''), 'rpta-normal', [
            'alignment' => Jc::CENTER,
        ]);

        $cell3 = $table->addCell($anchoBloqueDerecho, array_merge(
            $this->estiloCelda(6, 6, 6, 6),
            ['gridSpan' => 4]
        ));
        $cell3->addText((string)($indicador['formula_descripcion'] ?? ''), 'rpta-normal', [
            'alignment' => Jc::START,
        ]);

        // =====================================================
        // ACCIONES
        // =====================================================
        $acciones = $indicador['acciones'] ?? [];

        if (empty($acciones)) {
            $table->addRow(320);

            $c1 = $table->addCell($anchoBloqueIzquierdo, array_merge(
                $this->estiloCelda(6, 6, 6, 6, 'DAE9F7'),
                [
                    'gridSpan' => 2,
                    'valign'   => 'center',
                ]
            ));
            $c1->addText('Acción', 'rpta-header-dark', [
                'alignment' => Jc::CENTER,
            ]);

            $c2 = $table->addCell($anchoBloqueCentral, array_merge(
                $this->estiloCelda(6, 6, 6, 6),
                ['gridSpan' => 2]
            ));
            $c2->addText('', 'rpta-normal', [
                'alignment' => Jc::CENTER,
            ]);

            $c3 = $table->addCell($anchoBloqueDerecho, array_merge(
                $this->estiloCelda(40, 40, 120, 120),
                [
                    'gridSpan' => 4,
                    'valign'   => 'center',
                ]
            ));
            $c3->addText('Sin acciones registradas.', 'rpta-normal', [
                'alignment'   => Jc::START,
                'spaceBefore' => 0,
                'spaceAfter'  => 0,
            ]);
        } else {
            foreach ($acciones as $i => $accion) {
                $table->addRow(320);

                $c1 = $table->addCell($anchoBloqueIzquierdo, array_merge(
                    $this->estiloCelda(6, 6, 6, 6, 'DAE9F7'),
                    [
                        'gridSpan' => 2,
                        'valign'   => 'center',
                    ]
                ));
                $c1->addText('Acción ' . ($i + 1), 'rpta-header-dark', [
                    'alignment' => Jc::CENTER,
                ]);

                $c2 = $table->addCell($anchoBloqueCentral, array_merge(
                    $this->estiloCelda(6, 6, 6, 6),
                    ['gridSpan' => 2]
                ));
                $c2->addText('', 'rpta-normal', [
                    'alignment' => Jc::CENTER,
                ]);

                $c3 = $table->addCell($anchoBloqueDerecho, array_merge(
                    $this->estiloCelda(40, 40, 120, 120),
                    [
                        'gridSpan' => 4,
                        'valign'   => 'center',
                    ]
                ));
                $c3->addText((string)($accion['descripcion'] ?? ''), 'rpta-normal', [
                    'alignment'   => Jc::START,
                    'spaceBefore' => 0,
                    'spaceAfter'  => 0,
                ]);
            }
        }

        // =====================================================
        // EVIDENCIAS: UNA SOLA FILA
        // =====================================================
        $table->addRow();

        $cellEvidencias = $table->addCell($anchoTotal, array_merge(
            $this->estiloCelda(8, 8, 8, 8),
            ['gridSpan' => 8]
        ));

        $cellEvidencias->addText('EVIDENCIAS', 'rpta-header-dark', [
            'alignment' => Jc::CENTER,
        ]);

        $evidencias = $indicador['evidencias'] ?? [];

        if (empty($evidencias)) {
            $cellEvidencias->addTextBreak(1);
            $cellEvidencias->addText('Sin evidencias registradas.', 'rpta-normal', [
                'alignment' => Jc::START,
            ]);
            return;
        }

        foreach ($evidencias as $i => $evidencia) {
            $cellEvidencias->addTextBreak(1);

            $this->agregarTextoCompuesto(
                $cellEvidencias,
                'Evidencia ' . ($i + 1) . ': ',
                (string)($evidencia['descripcion'] ?? ''),
                false
            );

            $imagenesValidas = array_values(array_filter(
                $evidencia['imagenes'] ?? [],
                fn($img) => !empty($img['exists'])
            ));

            if (!empty($imagenesValidas)) {
                $cellEvidencias->addTextBreak(1);
                $this->agregarImagenesEnMismaCelda($cellEvidencias, $imagenesValidas);
            }
        }
    }

    private function agregarImagenesEnMismaCelda($cell, array $imagenes): void
    {
        $imagenesValidas = array_values(array_filter(
            $imagenes,
            fn($imagen) => !empty($imagen['path']) && is_file($imagen['path'])
        ));

        if (empty($imagenesValidas)) {
            $cell->addText('Imagen no encontrada.', 'rpta-normal', [
                'alignment' => Jc::CENTER,
            ]);
            return;
        }

        $count = count($imagenesValidas);

        if ($count === 1) {
            $this->agregarImagenesEnFila($cell, $imagenesValidas, 340, 240, '  ');
            return;
        }

        if ($count === 2) {
            $this->agregarImagenesEnFila($cell, $imagenesValidas, 230, 165, '   ');
            return;
        }

        if ($count === 3) {
            $this->agregarImagenesEnFila($cell, $imagenesValidas, 150, 110, '  ');
            return;
        }

        $primeraFila = array_slice($imagenesValidas, 0, 2);
        $segundaFila = array_slice($imagenesValidas, 2, 2);

        $this->agregarImagenesEnFila($cell, $primeraFila, 180, 130, '   ');

        if (!empty($segundaFila)) {
            $cell->addTextBreak(1);
            $this->agregarImagenesEnFila($cell, $segundaFila, 180, 130, '   ');
        }
    }

    private function agregarImagenesEnFila(
        $cell,
        array $imagenes,
        int $maxWidth,
        int $maxHeight,
        string $espaciado = '  '
    ): void {
        $textRun = $cell->addTextRun([
            'alignment' => Jc::CENTER,
        ]);

        $total = count($imagenes);

        foreach ($imagenes as $index => $imagen) {
            $path = (string)($imagen['path'] ?? '');

            if (!is_file($path)) {
                $textRun->addText('Imagen no encontrada.', 'rpta-normal');
            } else {
                [$width, $height] = $this->calcularTamanoImagen($path, $maxWidth, $maxHeight);

                $textRun->addImage($path, [
                    'width'  => $width,
                    'height' => $height,
                ]);
            }

            if ($index < $total - 1 && $espaciado !== '') {
                $textRun->addText($espaciado, 'rpta-normal');
            }
        }
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

    private function agregarTextoMultilineaCentrado($cell, array $lineas, string $fontStyle): void
    {
        $textRun = $cell->addTextRun([
            'alignment' => Jc::CENTER,
        ]);

        $total = count($lineas);

        foreach ($lineas as $index => $linea) {
            $textRun->addText((string)$linea, $fontStyle);

            if ($index < $total - 1) {
                $textRun->addTextBreak();
            }
        }
    }

    private function agregarTextoCompuesto($cell, string $bold, string $normal, bool $center = false): void
    {
        $textRun = $cell->addTextRun([
            'alignment' => $center ? Jc::CENTER : Jc::START,
        ]);

        if ($bold !== '') {
            $textRun->addText($bold, 'rpta-bold');
        }

        $textRun->addText($normal, 'rpta-normal');
    }

    private function agregarImagenCentrada($cell, string $path, int $maxWidth, int $maxHeight): void
    {
        if (!is_file($path)) {
            $cell->addText('Imagen no encontrada.', 'rpta-normal', [
                'alignment' => Jc::CENTER,
            ]);
            return;
        }

        [$width, $height] = $this->calcularTamanoImagen($path, $maxWidth, $maxHeight);

        $cell->addImage($path, [
            'width'     => $width,
            'height'    => $height,
            'alignment' => Jc::CENTER,
        ]);
    }

    private function calcularTamanoImagen(string $path, int $maxWidth, int $maxHeight): array
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

    private function estiloCelda(
        int $marginTop = 6,
        int $marginBottom = 6,
        int $marginLeft = 6,
        int $marginRight = 6,
        ?string $bgColor = null
    ): array {
        $style = [
            'borderSize'   => 6,
            'borderColor'  => '000000',
            'marginTop'    => $marginTop,
            'marginBottom' => $marginBottom,
            'marginLeft'   => $marginLeft,
            'marginRight'  => $marginRight,
        ];

        if ($bgColor !== null) {
            $style['bgColor'] = $bgColor;
        }

        return $style;
    }
}