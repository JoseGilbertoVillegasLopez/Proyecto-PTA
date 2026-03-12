<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class PtaExcelExportService
{
    public function export(Encabezado $encabezado): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('PTA');

        /* =================================================
         * FUENTE BASE
         * ================================================= */
        $spreadsheet->getDefaultStyle()->getFont()
            ->setName('Calibri')
            ->setSize(10);

        /* =================================================
         * ANCHOS DE COLUMNA (NO TOCAR)
         * ================================================= */
        $anchoAE = 11.51;
        $anchoFQ = 4;

        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setWidth($anchoAE);
        }
        foreach (range('F', 'Q') as $col) {
            $sheet->getColumnDimension($col)->setWidth($anchoFQ);
        }
        foreach (range('R', 'Z') as $col) {
            $sheet->getColumnDimension($col)->setVisible(false);
        }

        /* =================================================
         * LOGO (ÚNICO AGREGADO)
         * ================================================= */
        $logoPath = __DIR__ . '/../../../public/assets/img/logo.png';

        if (file_exists($logoPath)) {
            $logo = new Drawing();
            $logo->setPath($logoPath);
            $logo->setCoordinates('A1');
            $logo->setHeight(80);
            $logo->setOffsetX(10);
            $logo->setOffsetY(5);
            $logo->setWorksheet($sheet);
        }

        $row = 1;

        $row = $this->buildHeader($sheet, $encabezado);
        $row++;

        $row = $this->buildIndicadores($sheet, $encabezado, $row);

        $sheet->mergeCells("A{$row}:Q{$row}");
        $row++;

        $row = $this->buildAcciones($sheet, $encabezado, $row);
        $row++;

        $this->buildResponsables($sheet, $encabezado, $row);

        $ultimaFila = $sheet->getHighestRow();
        $sheet->getPageSetup()->setPrintArea("A1:Q{$ultimaFila}");


        return $spreadsheet;
    }

    /* =====================================================
     * BLOQUE A — ENCABEZADO
     * ===================================================== */
    private function buildHeader(Worksheet $sheet, Encabezado $encabezado): int
    {
        $vino = '6B2D2A';

        $sheet->mergeCells('A1:B3');
        $sheet->mergeCells('C1:Q3');
        $sheet->mergeCells('A4:Q4');
        $sheet->mergeCells('A5:B5');
        $sheet->mergeCells('C5:Q5');
        $sheet->mergeCells('A6:B6');
        $sheet->mergeCells('C6:Q6');
        $sheet->mergeCells('A7:B7');
        $sheet->mergeCells('C7:Q7');

        $titulo =
            'INSTITUTO TECNOLÓGICO SUPERIOR DE COSAMALOAPAN' . PHP_EOL .
            'PROGRAMA DE TRABAJO ANUAL ' . $encabezado->getAnioEjecucion();

        $sheet->setCellValue('C1', $titulo);

        $sheet->getStyle('C1')->getFont()
            ->setSize(14)
            ->setBold(true);

        $sheet->getStyle('C1')->getAlignment()
            ->setWrapText(true)
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $this->autoHeightByText($sheet, 1, $titulo);

        $sheet->setCellValue('A5', 'Objetivo');
        $sheet->setCellValue('C5', $encabezado->getObjetivo());
        $this->wrapAndAuto($sheet, 'C5', 5, $encabezado->getObjetivo());

        $sheet->setCellValue('A6', 'Número de proyecto');
        $sheet->setCellValue('A7', $encabezado->getId());

        $sheet->setCellValue('C6', 'Nombre del proyecto');
        $sheet->setCellValue('C7', $encabezado->getNombre());
        $this->wrapAndAuto($sheet, 'C7', 7, $encabezado->getNombre());

        $this->applyHeaderColor($sheet, 'A5:B5', $vino);
        $this->applyHeaderColor($sheet, 'A6:B6', $vino);
        $this->applyHeaderColor($sheet, 'C6:Q6', $vino);

        $this->centerRange($sheet, 'A1:Q7');

        $sheet->getStyle('A1:Q7')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        return 7;
    }

    /* =====================================================
     * BLOQUE B — INDICADORES
     * ===================================================== */
    private function buildIndicadores(Worksheet $sheet, Encabezado $encabezado, int $row): int
    {
        $vino = '6B2D2A';
        $contador = 1;

        foreach ($encabezado->getIndicadores() as $indicador) {

            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->mergeCells("C{$row}:E{$row}");
            $sheet->mergeCells("F{$row}:K{$row}");
            $sheet->mergeCells("L{$row}:Q{$row}");

            $sheet->setCellValue("A{$row}", "Indicador {$contador}");
            $sheet->setCellValue("C{$row}", 'Fórmula');
            $sheet->setCellValue("F{$row}", 'Valor a alcanzar');
            $sheet->setCellValue("L{$row}", 'Periodicidad');

            $this->applyHeaderColor($sheet, "A{$row}:Q{$row}", $vino);
            $this->centerRange($sheet, "A{$row}:Q{$row}");

            $row++;

            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->mergeCells("C{$row}:E{$row}");
            $sheet->mergeCells("F{$row}:K{$row}");
            $sheet->mergeCells("L{$row}:Q{$row}");

            $sheet->setCellValue("A{$row}", $indicador->getIndicador());
            $sheet->setCellValue("C{$row}", $indicador->getFormula());
            $sheet->setCellValue("F{$row}", $indicador->getValor());
            $sheet->setCellValue("L{$row}", $indicador->getPeriodo());

            $this->wrapAndAuto($sheet, "A{$row}", $row, $indicador->getIndicador());
            $this->wrapAndAuto($sheet, "C{$row}", $row, $indicador->getFormula());
            $this->centerRange($sheet, "A{$row}:Q{$row}");

            $sheet->getStyle("A{$row}:Q{$row}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            $row++;
            $contador++;
        }

        return $row;
    }

    /* =====================================================
     * BLOQUE C — ACCIONES
     * ===================================================== */
    private function buildAcciones(Worksheet $sheet, Encabezado $encabezado, int $row): int
    {
        $vino = '6B2D2A';
        $amarillo = 'FFF200';

        $meses = [
            'Enero','Febrero','Marzo','Abril','Mayo','Junio',
            'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
        ];

        $sheet->mergeCells("A{$row}:E".($row+1));
        $sheet->mergeCells("F{$row}:Q{$row}");

        $sheet->setCellValue("A{$row}", 'Acciones');
        $sheet->setCellValue("F{$row}", 'Periodo de ejecución');

        $this->applyHeaderColor($sheet, "A{$row}:Q".($row+1), $vino);
        $this->centerRange($sheet, "A{$row}:Q".($row+1));

        $row++;

        foreach ($meses as $i => $mes) {
            $col = chr(ord('F') + $i);
            $sheet->setCellValue("{$col}{$row}", mb_substr($mes, 0, 3));
            $this->centerRange($sheet, "{$col}{$row}");
        }

        $row++;

        foreach ($encabezado->getAcciones() as $accion) {

            $sheet->mergeCells("A{$row}:E{$row}");
            $sheet->setCellValue("A{$row}", $accion->getAccion());

            $sheet->getStyle("A{$row}")->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);

            $this->autoHeightByText($sheet, $row, $accion->getAccion());

            $valores = $accion->getValorAlcanzado() ?? [];

            $periodoAccion = $accion->getPeriodo() ?? [];

            foreach ($meses as $i => $mes) {
                $col = chr(ord('F') + $i);

                // 1️⃣ Valor (si existe)
                if (array_key_exists($mes, $valores) && $valores[$mes] !== null) {
                    $sheet->setCellValue("{$col}{$row}", $valores[$mes]);
                    $this->centerRange($sheet, "{$col}{$row}");
                }

                // 2️⃣ Color amarillo SOLO depende del periodo
                if (in_array($mes, $periodoAccion, true)) {
                    $sheet->getStyle("{$col}{$row}")
                        ->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()
                        ->setRGB($amarillo);
                }
            }


            $sheet->getStyle("A{$row}:Q{$row}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            $row++;
        }

        return $row;
    }

    /* =====================================================
     * BLOQUE D — RESPONSABLES
     * ===================================================== */
    private function buildResponsables(Worksheet $sheet, Encabezado $encabezado, int $row): void
    {
        $resp = $encabezado->getResponsables();

        $filas = [
            ['label' => 'Responsable del proyecto', 'persona' => $encabezado->getResponsable()],
            ['label' => 'Supervisor', 'persona' => $resp?->getSupervisor()],
            ['label' => 'Aval', 'persona' => $resp?->getAval()],
        ];

        foreach ($filas as $item) {

            $persona = $item['persona'];
            $nombre = $persona ? (string)$persona : '';
            $puesto = ($persona && $persona->getPuesto())
                ? $persona->getPuesto()->getNombre()
                : '';

            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->mergeCells("C{$row}:D{$row}");
            $sheet->mergeCells("E{$row}:J{$row}");
            $sheet->mergeCells("K{$row}:Q{$row}");

            $sheet->setCellValue("A{$row}", $item['label']);
            $sheet->setCellValue("C{$row}", $nombre);
            $sheet->setCellValue("E{$row}", $puesto);

            $sheet->getStyle("A{$row}:C{$row}")->getFont()->setBold(true);

            $this->centerRange($sheet, "A{$row}:Q{$row}");
            $this->wrapAndAuto($sheet, "C{$row}", $row, $nombre);
            $this->wrapAndAuto($sheet, "E{$row}", $row, $puesto);

            $sheet->getStyle("A{$row}:Q{$row}")
                ->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            $row++;
        }
    }

    /* =====================================================
     * HELPERS
     * ===================================================== */
    private function applyHeaderColor(Worksheet $sheet, string $range, string $color): void
    {
        $sheet->getStyle($range)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB($color);

        $sheet->getStyle($range)->getFont()
            ->setBold(true)
            ->setSize(10)
            ->getColor()->setRGB('FFFFFF');
    }

    private function centerRange(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function wrapAndAuto(Worksheet $sheet, string $cell, int $row, string $text): void
    {
        $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
        $this->autoHeightByText($sheet, $row, $text);
    }

    private function autoHeightByText(Worksheet $sheet, int $row, string $text): void
    {
        $lineas = max(1, ceil(strlen($text) / 35));
        $sheet->getRowDimension($row)->setRowHeight(max(30, $lineas * 18));
    }
}
