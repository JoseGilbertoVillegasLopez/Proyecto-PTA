<?php

namespace App\Controller;

use App\Entity\Encabezado;
use App\Entity\Personal;
use App\Form\EncabezadoType;
use App\Repository\EncabezadoRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/encabezado')]
final class AdminEncabezadoController extends AbstractController
{
    #[Route(name: 'app_encabezado_index', methods: ['GET'])]
public function index(
    Request $request,
    EncabezadoRepository $encabezadoRepository
): Response
{
    // Año actual por defecto
    $anioActual = (int) date('Y');

    // Año de ejecución seleccionado (GET) o año actual
    $anioEjecucion = $request->query->getInt('anio', $anioActual);

    $encabezados = $encabezadoRepository->createQueryBuilder('e')
        ->andWhere('e.anioEjecucion = :anio')
        ->setParameter('anio', $anioEjecucion)
        ->orderBy('e.fechaCreacion', 'DESC')
        ->getQuery()
        ->getResult();

    return $this->render('admin/encabezado/index.html.twig', [
        'encabezados' => $encabezados,
        'anioSeleccionado' => $anioEjecucion,
    ]);
}



    #[Route('/new', name: 'app_encabezado_new', methods: ['GET', 'POST'])]
        public function new(
            Request $request,
            EntityManagerInterface $entityManager,
            EncabezadoRepository $encabezadoRepository
        ): Response
        {
            // Año actual por defecto
            $anioActual = (int) date('Y');

            // Año de ejecución seleccionado (GET) o año actual
            $anioEjecucion = $request->query->getInt('anio', $anioActual);

            $encabezados = $encabezadoRepository->createQueryBuilder('e')
                ->andWhere('e.anioEjecucion = :anio')
                ->setParameter('anio', $anioEjecucion)
                ->orderBy('e.fechaCreacion', 'DESC')
                ->getQuery()
                ->getResult();
            /**
             * =========================================================
             * CREACIÓN DE LA ENTIDAD PRINCIPAL
             * ---------------------------------------------------------
             * Encabezado es la entidad raíz del PTA.
             * Todas las demás entidades (Responsables, Indicadores,
             * Acciones) dependen de esta.
             * =========================================================
             */
            $encabezado = new Encabezado();

            /**
             * =========================================================
             * INICIALIZACIÓN DE RESPONSABLES (OneToOne)
             * ---------------------------------------------------------
             * - Responsables es una relación OneToOne con Encabezado
             * - Se inicializa manualmente porque:
             *   - El FormType usa campos mapped=false
             *   - Symfony NO lo crea automáticamente
             * =========================================================
             */
            $responsables = new \App\Entity\Responsables();
            $encabezado->setResponsables($responsables);

            /**
             * =========================================================
             * RESPONSABLE PRINCIPAL (USUARIO LOGUEADO)
             * ---------------------------------------------------------
             * - El responsable NO es el supervisor ni el aval
             * - Es el Personal asociado al usuario autenticado
             * - Se asigna automáticamente al crear el PTA
             * =========================================================
             */
            $usuario = $this->getUser();
            if ($usuario instanceof \App\Entity\User && $usuario->getPersonal()) {
                $encabezado->setResponsable($usuario->getPersonal());
            }

            /**
             * =========================================================
             * CREACIÓN Y MANEJO DEL FORMULARIO
             * ---------------------------------------------------------
             * - EncabezadoType incluye:
             *   - ResponsablesType
             *   - CollectionType de Indicadores
             *   - CollectionType de Acciones
             * =========================================================
             */
            $form = $this->createForm(EncabezadoType::class, $encabezado);
            $form->handleRequest($request);

            /**
             * =========================================================
             * PROCESAMIENTO DEL SUBMIT
             * ---------------------------------------------------------
             * - El JS ya validó la lógica de negocio
             * - Aquí solo se persiste lo recibido
             * =========================================================
             */
            if ($form->isSubmitted() && $form->isValid()) {

                /**
                 * =====================================================
                 * PROCESAMIENTO MANUAL DE SUPERVISOR Y AVAL
                 * -----------------------------------------------------
                 * - Estos campos son mapped=false en el FormType
                 * - Se reciben como IDs dentro del request
                 * - Se asignan manualmente a la entidad Responsables
                 * =====================================================
                 */
                $responsables = $encabezado->getResponsables();

                $responsables = $encabezado->getResponsables();

                if ($responsables) {

                    // Obtener todos los datos del formulario Encabezado
                    $data = $request->request->all('encabezado');

                    // IDs enviados por los inputs hidden
                    $supervisorId = $data['responsables']['supervisor'] ?? null;
                    $avalId       = $data['responsables']['aval'] ?? null;

                    // Asignación del Supervisor (Personal)
                    if ($supervisorId) {
                        $supervisor = $entityManager
                            ->getRepository(Personal::class)
                            ->find($supervisorId);

                        $responsables->setSupervisor($supervisor);
                    }

                    // Asignación del Aval (Personal)
                    if ($avalId) {
                        $aval = $entityManager
                            ->getRepository(Personal::class)
                            ->find($avalId);

                        $responsables->setAval($aval);
                    }
                }

                /**
                 * =====================================================
                 * METADATOS DEL PTA
                 * -----------------------------------------------------
                 * - Fecha de creación
                 * - Estatus inicial activo
                 * =====================================================
                 */
                $encabezado->setFechaCreacion(new \DateTime());
                $encabezado->setStatus(true);

                /**
                 * =====================================================
                 * ASEGURAR RELACIÓN PADRE → HIJOS
                 * -----------------------------------------------------
                 * - Doctrine NO asigna automáticamente la relación
                 * - Se hace manual para:
                 *   - Indicadores
                 *   - Acciones
                 * =====================================================
                 */
                foreach ($encabezado->getIndicadores() as $indicador) {
                    $indicador->setEncabezado($encabezado);
                }

                foreach ($encabezado->getAcciones() as $accion) {
                    $accion->setEncabezado($encabezado);
                }

                /**
                 * =====================================================
                 * REAFIRMAR RESPONSABLE PRINCIPAL
                 * -----------------------------------------------------
                 * - Se vuelve a asignar por seguridad
                 * - Garantiza que el PTA quede ligado al creador
                 * =====================================================
                 */
                $usuario = $this->getUser();
                if ($usuario instanceof \App\Entity\User && $usuario->getPersonal()) {
                    $encabezado->setResponsable($usuario->getPersonal());
                }

                /**
                 * =====================================================
                 * PERSISTENCIA FINAL
                 * -----------------------------------------------------
                 * - Persistimos solo el Encabezado
                 * - Las relaciones se guardan por cascade
                 * =====================================================
                 */
                $entityManager->persist($encabezado);
                $entityManager->flush();

                /**
                 * =====================================================
                 * REDIRECCIÓN POST-GUARDADO
                 * -----------------------------------------------------
                 * - Se regresa al index con todos los PTAs
                 * =====================================================
                 */
                return $this->render('admin/encabezado/index.html.twig', [
                    'encabezados' => $encabezados,
                    'anioSeleccionado' => $anioEjecucion,
                ]);
            }

            /**
             * =========================================================
             * RENDER DE LA VISTA NEW (GET o FORM INVÁLIDO)
             * =========================================================
             */
            return $this->render('admin/encabezado/new.html.twig', [
                'encabezado' => $encabezado,
                'form' => $form,
            ]);
        }


    #[Route('/{id}', name: 'app_encabezado_show', methods: ['GET'])]
    public function show(Encabezado $encabezado): Response
    {
        return $this->render('admin/encabezado/show.html.twig', [
            'encabezado' => $encabezado,
        ]);
    }



    #[Route('/{id}/edit', name: 'app_encabezado_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Encabezado $encabezado,
        EntityManagerInterface $entityManager,
        EncabezadoRepository $encabezadoRepository
    ): Response
    {
        /**
         * =====================================================
         * BLOQUE POST
         * =====================================================
         * Este bloque se ejecuta únicamente cuando el usuario
         * envía el formulario de captura de avance mensual.
         *
         * Responsabilidad:
         *  - Validar CSRF
         *  - Leer valores enviados por acción y mes
         *  - Normalizar valores vacíos
         *  - Guardar el avance (JSON) en cada acción
         *  - Retornar al index manteniendo el año seleccionado
         * =====================================================
         */
        if ($request->isMethod('POST')) {

            /**
             * -------------------------------------------------
             * 1. Determinar el año de ejecución
             * -------------------------------------------------
             * - Se usa el año actual como valor por defecto
             * - Si viene un año por query (?anio=XXXX),
             *   se respeta para mantener el contexto
             */
            $anioActual = (int) date('Y');

            $anioEjecucion = $request->query->getInt('anio', $anioActual);

            /**
             * -------------------------------------------------
             * 2. Obtener encabezados del año seleccionado
             * -------------------------------------------------
             * Estos datos NO afectan la edición.
             * Se usan únicamente para renderizar correctamente
             * la vista index después de guardar el avance.
             */
            $encabezados = $encabezadoRepository->createQueryBuilder('e')
                ->andWhere('e.anioEjecucion = :anio')
                ->setParameter('anio', $anioEjecucion)
                ->orderBy('e.fechaCreacion', 'DESC')
                ->getQuery()
                ->getResult();

            /**
             * -------------------------------------------------
             * 3. Validación de token CSRF
             * -------------------------------------------------
             * Protege el formulario contra ataques CSRF.
             * El token está ligado al ID del encabezado.
             */
            if (
                !$this->isCsrfTokenValid(
                    'edit' . $encabezado->getId(),
                    $request->request->get('_token')
                )
            ) {
                throw $this->createAccessDeniedException('Token CSRF inválido');
            }

            /**
             * -------------------------------------------------
             * 4. Obtener valores enviados del formulario
             * -------------------------------------------------
             * Estructura esperada:
             * [
             *   accion_id => [
             *     'Enero' => valor,
             *     'Febrero' => valor,
             *     ...
             *   ]
             * ]
             */
            $valoresAlcanzados = $request->request->all('valor_alcanzado');

            /**
             * -------------------------------------------------
             * 5. Recorrer acciones del encabezado
             * -------------------------------------------------
             * Solo se procesan acciones que pertenecen
             * al encabezado actual.
             */
            foreach ($encabezado->getAcciones() as $accion) {

                $accionId = $accion->getId();

                // Si la acción no viene en el POST, se omite
                if (!isset($valoresAlcanzados[$accionId])) {
                    continue;
                }

                $meses = $valoresAlcanzados[$accionId];

                /**
                 * -------------------------------------------------
                 * 6. Normalización de valores
                 * -------------------------------------------------
                 * Convierte strings vacíos ("") a null para:
                 *  - Diferenciar entre "sin captura" y "valor 0"
                 *  - Evitar datos inconsistentes en el JSON
                 */
                foreach ($meses as $mes => $valor) {
                    if ($valor === '') {
                        $meses[$mes] = null;
                    }
                }

                /**
                 * -------------------------------------------------
                 * 7. Guardar avance mensual en la acción
                 * -------------------------------------------------
                 * El avance se guarda como JSON asociado
                 * directamente a la acción.
                 */
                $accion->setValorAlcanzado($meses);
            }

            /**
             * -------------------------------------------------
             * 8. Persistir cambios en base de datos
             * -------------------------------------------------
             * No se usa persist() porque las acciones ya
             * están administradas por Doctrine.
             */
            $entityManager->flush();

            /**
             * -------------------------------------------------
             * 9. Retornar a la vista index
             * -------------------------------------------------
             * Se renderiza directamente la vista index
             * manteniendo el año seleccionado.
             * (No se usa redirect por diseño del flujo)
             */
            return $this->render('admin/encabezado/index.html.twig', [
                'encabezados'      => $encabezados,
                'anioSeleccionado' => $anioEjecucion,
            ]);
        }



            if ($encabezado->getResponsables() === null) {
                $encabezado->setResponsables(new \App\Entity\Responsables());
            }

            return $this->render('admin/encabezado/edit.html.twig', [
                'encabezado' => $encabezado,
            ]);
    }

    #[Route('/{id}', name: 'app_encabezado_delete', methods: ['POST'])]
    public function delete(Request $request, Encabezado $encabezado, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$encabezado->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($encabezado);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_encabezado_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/graficas/{id}', name: 'app_encabezado_graficas', methods: ['GET'])]
    public function graficas(Encabezado $encabezado): Response
    {
        /**
         * =====================================================
         * MÓDULO GRÁFICAS PTA
         * -----------------------------------------------------
         * Este método construye TODA la información necesaria
         * para la visualización gráfica de un PTA.
         *
         * Reglas clave:
         * - TODOS los cálculos se hacen en PHP
         * - El frontend (JS) solo dibuja
         * - La vista recibe datos ya procesados
         * =====================================================
         */

        /**
         * -----------------------------------------------------
         * ORDEN FIJO DE MESES
         * -----------------------------------------------------
         * Se define explícitamente el orden de los meses
         * para:
         * - Mantener consistencia visual
         * - Evitar dependencias del orden en base de datos
         * - Garantizar coherencia entre series
         */
        $meses = [
            'Enero','Febrero','Marzo','Abril','Mayo','Junio',
            'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
        ];

        /**
         * Arreglo final que contendrá TODAS las gráficas
         * (una por indicador)
         */
        $graficas = [];

        /**
         * Acciones asociadas al encabezado
         * Se reutilizan varias veces para:
         * - Sumar valores
         * - Generar resúmenes
         */
        $acciones = $encabezado->getAcciones();

        /**
         * =====================================================
         * RECORRIDO PRINCIPAL POR INDICADORES
         * =====================================================
         * Cada indicador genera:
         * - Una serie mensual
         * - Un porcentaje de avance
         * - Un resumen por acciones
         */
        foreach ($encabezado->getIndicadores() as $indicador) {

            /**
             * =================================================
             * 1) SUMA MENSUAL REAL (BASE)
             * =================================================
             * Se calcula la suma REAL mensual considerando
             * todas las acciones que pertenecen al indicador.
             *
             * Resultado:
             * [
             *   'Enero' => total,
             *   'Febrero' => total,
             *   ...
             * ]
             */
            $valoresMensuales = array_fill_keys($meses, 0);

            foreach ($acciones as $accion) {

                // Ignorar acciones que no pertenecen al indicador
                if ($accion->getIndicador() !== $indicador->getIndice()) {
                    continue;
                }

                // Valores alcanzados por mes (JSON)
                $valoresAccion = $accion->getValorAlcanzado() ?? [];

                // Acumulación mensual
                foreach ($meses as $mes) {
                    if (isset($valoresAccion[$mes])) {
                        $valoresMensuales[$mes] += (float) $valoresAccion[$mes];
                    }
                }
            }

            /**
             * Meta y tipo de tendencia del indicador
             */
            $meta      = (float) $indicador->getValor();
            $tendencia = $indicador->getTendencia();

            /**
             * =================================================
             * 2) CONSTRUCCIÓN DE LA SERIE FINAL
             * =================================================
             * La serie depende del tipo de tendencia:
             *
             * POSITIVA:
             * - Acumulado progresivo
             *
             * NEGATIVA:
             * - Valor real más reciente
             * - Mientras no haya valor, se conserva el último
             */
            $serie = [];
            $ultimoValor = 0;

            if ($tendencia === 'POSITIVA') {

                // Tendencia positiva → acumulado
                $acumulado = 0;

                foreach ($meses as $mes) {
                    $acumulado += $valoresMensuales[$mes];
                    $serie[$mes] = $acumulado;
                }

                // Porcentaje de avance respecto a la meta
                $avanceFinal = end($serie);
                $porcentaje = ($meta > 0)
                    ? round(($avanceFinal / $meta) * 100, 1)
                    : 0;

            } else {

                // Tendencia negativa → valor real (no acumulado)
                foreach ($meses as $mes) {
                    if ($valoresMensuales[$mes] > 0) {
                        $ultimoValor = $valoresMensuales[$mes];
                    }
                    $serie[$mes] = $ultimoValor;
                }

                $avanceFinal = end($serie);

                /**
                 * En tendencia negativa:
                 * - Llegar o bajar a la meta = 100%
                 * - Si se excede, el porcentaje disminuye
                 */
                if ($meta > 0 && $avanceFinal > 0) {
                    $porcentaje = ($avanceFinal <= $meta)
                        ? 100
                        : round(($meta / $avanceFinal) * 100, 1);
                } else {
                    $porcentaje = 0;
                }
            }

            /**
             * Limitar porcentaje a rango válido [0, 100]
             */
            $porcentaje = max(0, min(100, $porcentaje));

            /**
             * =================================================
             * 3) RESUMEN POR ACCIONES
             * =================================================
             * Se calcula:
             * - Total por acción
             * - Distribución porcentual dentro del indicador
             * - Detalle mensual por acción
             */
            $accionesResumen = [];
            $totalIndicador = array_sum($valoresMensuales);

            foreach ($acciones as $accion) {

                // Filtrar solo acciones del indicador
                if ($accion->getIndicador() !== $indicador->getIndice()) {
                    continue;
                }

                $valoresAccion = $accion->getValorAlcanzado() ?? [];
                $totalAccion = 0;
                $mesesAccion = [];

                foreach ($meses as $mes) {
                    $valor = (float) ($valoresAccion[$mes] ?? 0);
                    $mesesAccion[$mes] = $valor;
                    $totalAccion += $valor;
                }

                // Porcentaje de contribución de la acción
                $porcentajeAccion = $totalIndicador > 0
                    ? round(($totalAccion / $totalIndicador) * 100, 1)
                    : 0;

                $accionesResumen[] = [
                    'nombre'     => $accion->getAccion(),
                    'meses'      => $mesesAccion,
                    'total'      => $totalAccion,
                    'porcentaje' => $porcentajeAccion,
                ];
            }

            /**
             * =================================================
             * 4) ARMADO FINAL DE LA GRÁFICA
             * =================================================
             * Este arreglo es consumido directamente
             * por la vista y el JS de Chart.js
             */
            $graficas[] = [
                'indicador'  => $indicador->getIndicador(),
                'meta'       => $meta,
                'tendencia'  => $tendencia,
                'meses'      => $serie,        // Serie FINAL
                'porcentaje' => $porcentaje,   // Avance global
                'acciones'   => $accionesResumen
            ];
        }

        /**
         * Render de la vista de gráficas
         */
        return $this->render('admin/encabezado/graficas.html.twig', [
            'encabezado' => $encabezado,
            'graficas'   => $graficas,
        ]);
    }

}
