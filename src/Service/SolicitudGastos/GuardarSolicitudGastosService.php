<?php

namespace App\Service\SolicitudGastos;

use App\Entity\Personal;
use App\Entity\SolicitudGastos;
use App\Entity\SolicitudGastosPartida;
use App\Repository\PartidasPresupuestalesRepository;
use App\Repository\ProcesoClaveRepository;
use App\Repository\ProcesoEstrategicoRepository;
use App\Repository\SolicitudGastosBancoRepository;
use App\Repository\TipoSolicitudRepository;
use Doctrine\ORM\EntityManagerInterface;

class GuardarSolicitudGastosService
{
    public function __construct(
        private readonly TipoSolicitudRepository $tipoSolicitudRepository,
        private readonly ProcesoEstrategicoRepository $procesoEstrategicoRepository,
        private readonly ProcesoClaveRepository $procesoClaveRepository,
        private readonly PartidasPresupuestalesRepository $partidasRepository,
        private readonly SolicitudGastosBancoRepository $bancoRepository,
        private readonly EntityManagerInterface $em,
    ) {}

    public function guardar(array $data, Personal $solicitante): SolicitudGastos
    {
        $solicitud = new SolicitudGastos();
        $solicitud->setSolicitante($solicitante);

        $tipoId = (int) ($data['tipo_solicitud'] ?? 0);
        $tipo = $this->tipoSolicitudRepository->find($tipoId);
        if ($tipo) {
            $solicitud->setTipoSolicitud($tipo);
        }

        $fechaNecesita = $data['fecha_necesita'] ?? null;
        if ($fechaNecesita) {
            $solicitud->setFechaNecesita(new \DateTime($fechaNecesita));
        }

        $transferencia = trim($data['transferencia_en_beneficio_de'] ?? '');
        $solicitud->setTransferenciaEnBeneficioDe($transferencia !== '' ? $transferencia : null);

        $ctaClave = trim($data['cta_clave_beneficiario'] ?? '');
        $solicitud->setCtaClaveBeneficiario($ctaClave !== '' ? $ctaClave : null);
        $solicitud->setPorConceptoDe(trim($data['por_concepto_de'] ?? ''));

        $peId = (int) ($data['proceso_estrategico'] ?? 0);
        if ($peId) {
            $pe = $this->procesoEstrategicoRepository->find($peId);
            if ($pe) {
                $solicitud->setProcesoEstrategico($pe);
            }
        }

        $pcId = (int) ($data['proceso_clave'] ?? 0);
        if ($pcId) {
            $pc = $this->procesoClaveRepository->find($pcId);
            if ($pc) {
                $solicitud->setProcesoClave($pc);
            }
        }

        $bancoId = (int) ($data['banco'] ?? 0);
        if ($bancoId) {
            $banco = $this->bancoRepository->find($bancoId);
            if ($banco) {
                $solicitud->setBanco($banco);
            }
        }

        $total = '0.00';
        $partidas = $data['partidas'] ?? [];

        foreach ($partidas as $item) {
            $partidaId = (int) ($item['partida_id'] ?? 0);
            $monto = $this->normalizarDecimal($item['monto'] ?? null);

            if (!$partidaId || $monto === null) {
                continue;
            }

            $partida = $this->partidasRepository->find($partidaId);
            if (!$partida) {
                continue;
            }

            $linea = new SolicitudGastosPartida();
            $linea->setPartida($partida);
            $linea->setMonto($monto);

            $solicitud->addPartida($linea);
            $total = number_format((float) $total + (float) $monto, 2, '.', '');
        }

        $solicitud->setCantidadTotal($total);

        $this->em->persist($solicitud);
        $this->em->flush();

        return $solicitud;
    }

    private function normalizarDecimal(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace([' ', ','], ['', '.'], $value);

        if (!is_numeric($value) || (float) $value <= 0) {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
