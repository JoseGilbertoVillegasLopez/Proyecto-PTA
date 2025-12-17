<?php

namespace App\Controller;

use App\Repository\PersonalRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * =========================================================
 * API ADMIN - PERSONAL
 * ---------------------------------------------------------
 * Controlador API utilizado por el módulo PTA
 * para búsquedas dinámicas de personal.
 *
 * NOTA:
 * - Este controlador NO renderiza vistas
 * - Solo devuelve JSON
 * - Es consumido exclusivamente por JavaScript
 * =========================================================
 */
#[Route('/admin/api/personal')]
final class AdminPersonalApiController extends AbstractController
{
    /**
     * =====================================================
     * BUSCAR PERSONAL
     * -----------------------------------------------------
     * Endpoint utilizado por los buscadores de:
     *  - Supervisor del Proyecto
     *  - Aval del Proyecto
     *
     * Flujo:
     * Vista (input) → JS (fetch) → API → JSON → JS → hidden
     *
     * IMPORTANTE:
     * - NO asigna relaciones
     * - NO persiste nada
     * - Solo sugiere resultados
     * =====================================================
     */
    #[Route('/buscar', name: 'admin_personal_buscar', methods: ['GET'])]
    public function buscar(
        Request $request,
        PersonalRepository $personalRepository
    ): JsonResponse
    {
        /**
         * =================================================
         * TEXTO DE BÚSQUEDA
         * -------------------------------------------------
         * - Se obtiene desde query string (?q=)
         * - Se castea a string y se limpia con trim
         * =================================================
         */
        $q = trim((string) $request->query->get('q', ''));

        /**
         * =================================================
         * VALIDACIÓN BÁSICA
         * -------------------------------------------------
         * - Evita consultas innecesarias
         * - Mejora performance
         * - Reduce carga en base de datos
         * =================================================
         */
        if (mb_strlen($q) < 2) {
            return $this->json([]);
        }

        /**
         * =================================================
         * CONSULTA A LA BASE DE DATOS
         * -------------------------------------------------
         * - Búsqueda case-insensitive
         * - Se buscan coincidencias parciales en:
         *   - nombre
         *   - apellido paterno
         *   - apellido materno
         * - Máximo 10 resultados
         * =================================================
         */
        $result = $personalRepository->createQueryBuilder('p')
            ->andWhere(
                'LOWER(p.nombre) LIKE :q 
                OR LOWER(p.ap_paterno) LIKE :q 
                OR LOWER(p.ap_materno) LIKE :q'
            )
            ->setParameter('q', '%' . mb_strtolower($q) . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        /**
         * =================================================
         * FORMATEO DE RESPUESTA
         * -------------------------------------------------
         * - Se construye un array simple
         * - Solo se expone:
         *   - id (clave real)
         *   - nombre completo (para mostrar)
         * =================================================
         */
        $data = [];

        foreach ($result as $p) {
            $data[] = [
                'id' => $p->getId(),
                'nombre' => trim(
                    $p->getNombre() . ' ' .
                    $p->getApPaterno() . ' ' .
                    $p->getApMaterno()
                ),
            ];
        }

        /**
         * =================================================
         * RESPUESTA FINAL
         * -------------------------------------------------
         * - JSON consumido directamente por JS
         * - No incluye información sensible
         * =================================================
         */
        return $this->json($data);
    }
}
