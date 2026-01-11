<?php

namespace App\Controller;

use App\Entity\Encabezado;
use App\Service\Pta\PtaExcelExportService;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EncabezadoExportController extends AbstractController
{
    #[Route('/encabezado/{id}/export/excel', name: 'app_encabezado_export_excel', methods: ['GET'])]
    public function exportExcel(
        Encabezado $encabezado,
        PtaExcelExportService $ptaExcelExportService
    ): Response {
        // 1️⃣ Generar el Spreadsheet usando el service
        $spreadsheet = $ptaExcelExportService->export($encabezado);

        // 2️⃣ Crear el writer (XLSX)
        $writer = new Xlsx($spreadsheet);

        // 3️⃣ Capturar la salida en memoria
        ob_start();
        $writer->save('php://output');
        $excelContent = ob_get_clean();

        // 4️⃣ Nombre del archivo
        $fileName = sprintf(
            'PTA_%s_%s.xlsx',
            $encabezado->getAnioEjecucion(),
            date('Ymd_His')
        );

        // 5️⃣ Devolver la respuesta HTTP
        return new Response(
            $excelContent,
            Response::HTTP_OK,
            [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
                'Cache-Control'       => 'max-age=0',
            ]
        );
    }
}
