<?php

namespace App\Form;

use App\Entity\PtaResponsableAdicional;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * =========================================================
 * FORM TYPE: RESPONSABLE ADICIONAL
 * ---------------------------------------------------------
 * Define el subformulario de un responsable adicional del PTA.
 *
 * IMPORTANTE:
 * - Sus campos de búsqueda son mapped = false
 * - El ID real se resuelve manualmente en el Controller
 *
 * A diferencia de ResponsablesType, este FormType SÍ se
 * asocia a una entidad real (PtaResponsableAdicional) porque
 * se usa dentro de un CollectionType con by_reference: false,
 * lo que permite que Symfony/Doctrine cree y vincule
 * automáticamente cada fila a Encabezado::$responsablesAdicionales.
 *
 * Se usa únicamente como:
 * - Contenedor de inputs visibles + hidden
 * - Apoyo al buscador dinámico vía JS
 * =========================================================
 */
class ResponsableAdicionalType extends AbstractType
{
    /**
     * =====================================================
     * DEFINICIÓN DEL SUBFORMULARIO DE RESPONSABLE ADICIONAL
     * =====================================================
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            /**
             * =============================================
             * PERSONAL (ID REAL)
             * ---------------------------------------------
             * - Campo hidden
             * - Guarda el ID real del Personal seleccionado
             * - mapped = false:
             *   - Symfony NO lo asigna automáticamente
             *   - Se procesa manualmente en el Controller
             * =============================================
             */
            ->add('personal', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])

            /**
             * =============================================
             * PERSONAL (INPUT VISIBLE)
             * ---------------------------------------------
             * - Input de texto para búsqueda
             * - NO se persiste
             * - El JS:
             *   - Llama a la API
             *   - Muestra sugerencias
             *   - Asigna el ID al campo hidden
             * =============================================
             */
            ->add('personal_search', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Responsable',
                'attr' => [
                    'class' => 'form-control personal-search',
                    'placeholder' => 'Buscar responsable...',
                    'autocomplete' => 'off',
                ],
            ]);
    }

    /**
     * =====================================================
     * CONFIGURACIÓN DEL FORM TYPE
     * -----------------------------------------------------
     * - Se asocia este subformulario con la entidad
     *   PtaResponsableAdicional (ManyToOne con Encabezado)
     * - Necesario para que el CollectionType pueda
     *   crear/vincular cada fila automáticamente
     * =====================================================
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PtaResponsableAdicional::class,
        ]);
    }
}
