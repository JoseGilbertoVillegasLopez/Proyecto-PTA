<?php

namespace App\Service\SolicitudGastos;

use App\Entity\SolicitudGastos;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Twig\Environment;

class SolicitudGastosPdfExportService
{
    private const DIAS_ES = [1 => 'lunes', 2 => 'martes', 3 => 'miércoles', 4 => 'jueves', 5 => 'viernes', 6 => 'sábado', 7 => 'domingo'];

    private const MESES_ES = [
        1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril', 5 => 'mayo', 6 => 'junio',
        7 => 'julio', 8 => 'agosto', 9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
    ];

    public function __construct(
        private readonly Environment $twig,
        private readonly MontoEnLetrasService $montoEnLetras,
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    public function exportar(SolicitudGastos $solicitud): BinaryFileResponse
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');

        $html = $this->twig->render('solicitud_gastos/pdf.html.twig', [
            'solicitud' => $solicitud,
            'montoEnLetra' => $this->montoEnLetras->convertir($solicitud->getCantidadTotal()),
            'fechaTexto' => $this->formatearFechaEspanol($solicitud->getFechaSolicitud()),
        ]);

        $tempDirMpdf = $projectDir . '/var/tmp/mpdf';
        if (!is_dir($tempDirMpdf)) {
            mkdir($tempDirMpdf, 0777, true);
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'Letter',
            'margin_top' => 6,
            'margin_bottom' => 6,
            'margin_left' => 6,
            'margin_right' => 6,
            'margin_header' => 0,
            'margin_footer' => 0,
            'default_font' => 'notosans',
            'tempDir' => $tempDirMpdf,
        ]);

        $mpdf->WriteHTML($html);

        $nombreArchivo = sprintf('solicitud_gastos_%s_%s.pdf', $solicitud->getId(), uniqid());
        $rutaTemporal = $projectDir . '/var/tmp/' . $nombreArchivo;

        if (!is_dir(dirname($rutaTemporal))) {
            mkdir(dirname($rutaTemporal), 0777, true);
        }

        $mpdf->Output($rutaTemporal, Destination::FILE);

        $response = new BinaryFileResponse($rutaTemporal);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($rutaTemporal)
        );
        $response->deleteFileAfterSend(true);

        return $response;
    }

    private function formatearFechaEspanol(\DateTimeInterface $fecha): string
    {
        $dia = self::DIAS_ES[(int) $fecha->format('N')];
        $mes = self::MESES_ES[(int) $fecha->format('n')];

        return mb_strtoupper(sprintf('%s, %d de %s de %s', $dia, (int) $fecha->format('j'), $mes, $fecha->format('Y')));
    }
}
