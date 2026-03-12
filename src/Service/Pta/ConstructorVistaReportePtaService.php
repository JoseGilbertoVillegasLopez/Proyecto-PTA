<?php

namespace App\Service\Pta;

use App\Entity\Encabezado;
use App\Repository\IndicadoresBasicosRepository;
use App\Repository\ProcesoClaveRepository;
use App\Repository\ProcesoEstrategicoRepository;
use App\Repository\PartidasPresupuestalesRepository;

class ConstructorVistaReportePtaService
{
    public function __construct(
        private PtaTrimestreCalculoService $calculoService,
        private IndicadoresBasicosRepository $indicadoresBasicosRepository,
        private ProcesoClaveRepository $procesoClaveRepository,
        private ProcesoEstrategicoRepository $procesoEstrategicoRepository,
        private PartidasPresupuestalesRepository $partidasRepository,
    ) {}

    public function build(Encabezado $encabezado, int $trimestre): array
    {
        // ============================
        // 1️⃣ Resultados matemáticos
        // ============================
        $resultados = $this->calculoService->build($encabezado, $trimestre);

        // ============================
        // 2️⃣ Catálogos activos
        // ============================
        $indicadoresBasicos = $this->indicadoresBasicosRepository->findBy(
            ['activo' => true],
            ['nombreIndicador' => 'ASC']
        );

        $procesosClaveEntities = $this->procesoClaveRepository->findBy(
    ['activo' => true],
    ['nombre' => 'ASC']
);

$procesosClave = array_map(fn($p) => [
    'id' => $p->getId(),
    'nombre' => $p->getNombre(),
], $procesosClaveEntities);


$procesosEstrategicosEntities = $this->procesoEstrategicoRepository->findBy(
    ['activo' => true],
    ['nombre' => 'ASC']
);

$procesosEstrategicos = array_map(fn($p) => [
    'id' => $p->getId(),
    'nombre' => $p->getNombre(),
], $procesosEstrategicosEntities);

        $partidasEntities = $this->partidasRepository->findBy(
    ['activo' => true],
    ['capitulo' => 'ASC']
);

$partidas = array_map(function ($p) {

    return [
        'id' => $p->getId(),
        'capitulo' => $p->getCapitulo(),
        'partida' => $p->getPartida(),
        'descripcion' => $p->getDescripcion(),
    ];

}, $partidasEntities);

        // ============================
        // 3️⃣ Datos del PTA
        // ============================
        $responsable = $encabezado->getResponsable();
        $puesto = $responsable?->getPuesto();

        return [
            'pta' => [
                'id' => $encabezado->getId(),
                'nombre' => $encabezado->getNombre(),
                'objetivo' => $encabezado->getObjetivo(),
                'anio' => $encabezado->getAnioEjecucion(),
                'puesto_responsable' => $puesto?->getNombre(),
            ],

            'trimestre' => $trimestre,

            'resultados' => $resultados,

            'catalogos' => [
                'indicadores_basicos' => $indicadoresBasicos,
                'procesos_clave' => $procesosClave,
                'procesos_estrategicos' => $procesosEstrategicos,
                'partidas_presupuestales' => $partidas,
            ]
        ];
    }
}
