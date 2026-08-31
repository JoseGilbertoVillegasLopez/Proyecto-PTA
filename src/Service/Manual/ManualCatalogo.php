<?php

namespace App\Service\Manual;

use App\Entity\User;
use App\Service\ModuloAcceso\ModuloAccesoResolver;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ManualCatalogo
{
    private const VISTA_LABELS = [
        'index' => 'Listado',
        'new' => 'Crear',
        'edit' => 'Editar',
        'show' => 'Detalle',
        'graficas' => 'Gráficas',
        'historial' => 'Historial',
        'encargado_index' => 'Revisión (Encargado)',
        'bancos' => 'Bancos',
        'configuracion' => 'Configuración',
    ];

    public function __construct(
        private readonly ModuloAccesoResolver $resolver,
        private readonly Security $security,
        #[Autowire(param: 'kernel.project_dir')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return array<int, array{slug:string,label:string,icono:string,vistas:array<int,array{slug:string,label:string}>}>
     */
    public function getModulosVisibles(): array
    {
        $user = $this->security->getUser();
        $esAdmin = $this->security->isGranted('ROLE_ADMIN');

        $modulos = [];
        foreach ($this->definiciones() as $def) {
            if (!($def['visible'])($esAdmin, $user)) {
                continue;
            }

            $vistas = $this->getVistas($def['slug']);
            if ($vistas === []) {
                continue;
            }

            $modulos[] = [
                'slug' => $def['slug'],
                'label' => $def['label'],
                'icono' => $def['icono'],
                'vistas' => $vistas,
            ];
        }

        return $modulos;
    }

    /**
     * @return array{slug:string,label:string,icono:string,vistas:array<int,array{slug:string,label:string}>}|null
     */
    public function getModulo(string $slug): ?array
    {
        $user = $this->security->getUser();
        $esAdmin = $this->security->isGranted('ROLE_ADMIN');

        foreach ($this->definiciones() as $def) {
            if ($def['slug'] !== $slug) {
                continue;
            }

            if (!($def['visible'])($esAdmin, $user)) {
                return null;
            }

            return [
                'slug' => $def['slug'],
                'label' => $def['label'],
                'icono' => $def['icono'],
                'vistas' => $this->getVistas($slug),
            ];
        }

        return null;
    }

    /**
     * @return array<int, array{slug:string,label:string}>
     */
    private function getVistas(string $slug): array
    {
        $dir = $this->projectDir.'/templates/manual/contenido/'.$slug;
        if (!is_dir($dir)) {
            return [];
        }

        $vistas = [];
        foreach (glob($dir.'/*.html.twig') ?: [] as $archivo) {
            $vistaSlug = basename($archivo, '.html.twig');
            $vistas[] = [
                'slug' => $vistaSlug,
                'label' => self::VISTA_LABELS[$vistaSlug] ?? ucfirst(str_replace('_', ' ', $vistaSlug)),
            ];
        }

        return $vistas;
    }

    /**
     * Espeja exactamente las condiciones de visibilidad ya usadas en el sidebar
     * de templates/dashboard/index.html.twig — ningún módulo controlado por
     * ModuloAccesoResolver tiene bypass de ROLE_ADMIN (política del proyecto),
     * salvo 'personal', que ya lo hace también en el sidebar real.
     *
     * @return array<int, array{slug:string,label:string,icono:string,visible:callable(bool,?User):bool}>
     */
    private function definiciones(): array
    {
        $tieneAcceso = fn (?User $user, string $modulo): bool => $user instanceof User && $this->resolver->tieneAcceso($user, $modulo);
        $esEncargado = fn (?User $user, string $modulo): bool => $user instanceof User && $this->resolver->esEncargado($user, $modulo);

        return [
            ['slug' => 'departamentos', 'label' => 'Departamentos', 'icono' => 'bi-building',
                'visible' => fn (bool $admin, ?User $user): bool => $admin],
            ['slug' => 'puestos', 'label' => 'Puestos', 'icono' => 'bi-briefcase',
                'visible' => fn (bool $admin, ?User $user): bool => $admin],
            ['slug' => 'modulos_acceso', 'label' => 'Gestión de Accesos', 'icono' => 'bi-shield-lock',
                'visible' => fn (bool $admin, ?User $user): bool => $admin],

            ['slug' => 'indicadores_basicos', 'label' => 'Indicadores Básicos', 'icono' => 'bi-bar-chart-line-fill',
                'visible' => fn (bool $admin, ?User $user): bool => true],
            ['slug' => 'partidas_presupuestales', 'label' => 'Partidas Presupuestales', 'icono' => 'bi-wallet2',
                'visible' => fn (bool $admin, ?User $user): bool => true],
            ['slug' => 'proceso_clave', 'label' => 'Proceso Clave', 'icono' => 'bi-diagram-3',
                'visible' => fn (bool $admin, ?User $user): bool => true],
            ['slug' => 'proceso_estrategico', 'label' => 'Proceso Estratégico', 'icono' => 'bi-diagram-2',
                'visible' => fn (bool $admin, ?User $user): bool => true],
            ['slug' => 'plantilla_indicadores', 'label' => 'Semáforo de Indicadores', 'icono' => 'bi-lightbulb',
                'visible' => fn (bool $admin, ?User $user): bool => true],

            ['slug' => 'personal', 'label' => 'Personal', 'icono' => 'bi-people-fill',
                'visible' => fn (bool $admin, ?User $user): bool => $admin || $tieneAcceso($user, 'personal')],

            ['slug' => 'pta', 'label' => 'PTA', 'icono' => 'bi-file-earmark-text',
                'visible' => fn (bool $admin, ?User $user): bool => $tieneAcceso($user, 'reportes_pta') || $esEncargado($user, 'reportes_pta')],
            ['slug' => 'historial_pta', 'label' => 'Historial PTA', 'icono' => 'bi-clock-history',
                'visible' => fn (bool $admin, ?User $user): bool => $tieneAcceso($user, 'reportes_pta') || $esEncargado($user, 'reportes_pta')],
            ['slug' => 'reporte_pta', 'label' => 'Reporte PTA', 'icono' => 'bi-file-earmark-bar-graph',
                'visible' => fn (bool $admin, ?User $user): bool => $tieneAcceso($user, 'reportes_pta') || $esEncargado($user, 'reportes_pta')],
            ['slug' => 'reporte_pta_encargado', 'label' => 'Reporte PTA — Encargado', 'icono' => 'bi-file-earmark-bar-graph',
                'visible' => fn (bool $admin, ?User $user): bool => $esEncargado($user, 'reportes_pta')],

            ['slug' => 'monitoreo', 'label' => 'Monitoreo PTA', 'icono' => 'bi-speedometer2',
                'visible' => fn (bool $admin, ?User $user): bool => $tieneAcceso($user, 'monitoreo')],

            ['slug' => 'solicitud_gastos', 'label' => 'Solicitud de Gastos', 'icono' => 'bi-receipt',
                'visible' => fn (bool $admin, ?User $user): bool => $tieneAcceso($user, 'solicitud_gastos')],
            ['slug' => 'solicitud_gastos_encargado', 'label' => 'Gastos — Encargado', 'icono' => 'bi-currency-dollar',
                'visible' => fn (bool $admin, ?User $user): bool => $esEncargado($user, 'solicitud_gastos')],

            ['slug' => 'reporte_indicadores', 'label' => 'Reportes de Indicadores', 'icono' => 'bi-file-earmark-bar-graph',
                'visible' => fn (bool $admin, ?User $user): bool => $tieneAcceso($user, 'reporte_indicadores') || $esEncargado($user, 'reporte_indicadores')],
            ['slug' => 'reporte_indicadores_encargado', 'label' => 'Indicadores — Encargado', 'icono' => 'bi-file-earmark-bar-graph',
                'visible' => fn (bool $admin, ?User $user): bool => $esEncargado($user, 'reporte_indicadores')],
        ];
    }
}
