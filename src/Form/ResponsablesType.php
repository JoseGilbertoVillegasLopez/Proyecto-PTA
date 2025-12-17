<?php

namespace App\Form;

use App\Entity\Encabezado;
use App\Entity\Personal;
use App\Entity\Responsables;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * =========================================================
 * FORM TYPE: RESPONSABLES
 * ---------------------------------------------------------
 * Define el subformulario de responsables del PTA.
 *
 * IMPORTANTE:
 * - Este FormType NO persiste datos directamente
 * - TODOS sus campos son mapped = false
 * - La asignación real se hace en el Controller (new)
 *
 * Se usa únicamente como:
 * - Contenedor de inputs visibles + hidden
 * - Apoyo al buscador dinámico vía JS
 * =========================================================
 */
class ResponsablesType extends AbstractType
{
    /**
     * =====================================================
     * DEFINICIÓN DEL SUBFORMULARIO DE RESPONSABLES
     * =====================================================
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            /**
             * =============================================
             * SUPERVISOR (ID REAL)
             * ---------------------------------------------
             * - Campo hidden
             * - Guarda el ID real del Personal seleccionado
             * - mapped = false:
             *   - Symfony NO lo asigna automáticamente
             *   - Se procesa manualmente en el Controller
             * =============================================
             */
            ->add('supervisor', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])

            /**
             * =============================================
             * SUPERVISOR (INPUT VISIBLE)
             * ---------------------------------------------
             * - Input de texto para búsqueda
             * - NO se persiste
             * - El JS:
             *   - Llama a la API
             *   - Muestra sugerencias
             *   - Asigna el ID al campo hidden
             * =============================================
             */
            ->add('supervisor_search', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Supervisor',
                'attr' => [
                    'class' => 'form-control supervisor-search',
                    'placeholder' => 'Buscar supervisor...',
                    'autocomplete' => 'off',
                ],
            ])

            /**
             * =============================================
             * AVAL (ID REAL)
             * ---------------------------------------------
             * - Campo hidden
             * - Mismo comportamiento que supervisor
             * =============================================
             */
            ->add('aval', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])

            /**
             * =============================================
             * AVAL (INPUT VISIBLE)
             * ---------------------------------------------
             * - Input para búsqueda dinámica
             * - Controlado completamente por JS
             * =============================================
             */
            ->add('aval_search', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Aval',
                'attr' => [
                    'class' => 'form-control aval-search',
                    'placeholder' => 'Buscar aval...',
                    'autocomplete' => 'off',
                ],
            ]);
    }

    /**
     * =====================================================
     * CONFIGURACIÓN DEL FORM TYPE
     * -----------------------------------------------------
     * - Se asocia este subformulario con la entidad
     *   Responsables (OneToOne con Encabezado)
     * =====================================================
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Responsables::class,
        ]);
    }
}
