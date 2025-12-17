<?php

namespace App\Form;

use App\Entity\Acciones;
use App\Entity\Encabezado;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * =========================================================
 * FORM TYPE: ACCIONES
 * ---------------------------------------------------------
 * Define la estructura de una ACCIÓN dentro del PTA.
 *
 * IMPORTANTE:
 * - Este FormType NO se renderiza directamente en la vista
 * - Se utiliza como CollectionType + prototype
 * - El JS se encarga de:
 *   - Insertar dinámicamente las filas
 *   - Renderizar visualmente los meses
 *   - Validar la información antes del submit
 * =========================================================
 */
class AccionesType extends AbstractType
{
    /**
     * =====================================================
     * DEFINICIÓN DEL FORMULARIO DE ACCIÓN
     * =====================================================
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            /**
             * =============================================
             * DESCRIPCIÓN DE LA ACCIÓN
             * ---------------------------------------------
             * - Texto descriptivo de la acción a realizar
             * - Textarea para permitir texto largo
             * - label = false porque:
             *   - La tabla ya da contexto visual
             * =============================================
             */
            ->add('accion', TextareaType::class, [
                'label' => false,
                'attr' => [
                    'rows' => 3,
                    'class' => 'accion-textarea',
                    'placeholder' => 'Descripción de la acción',
                ],
            ])

            /**
             * =============================================
             * INDICADOR ASOCIADO (ÍNDICE LÓGICO)
             * ---------------------------------------------
             * - Campo hidden
             * - NO es relación Doctrine
             * - NO es ID de base de datos
             * - Guarda el índice lógico del indicador
             * - El valor se asigna mediante JS al cambiar
             *   el select visible de indicadores
             * =============================================
             */
            ->add('indicador', HiddenType::class)

            /**
             * =============================================
             * PERIODO DE EJECUCIÓN (MESES)
             * ---------------------------------------------
             * - ChoiceType con meses del año
             * - multiple = true:
             *   - Permite seleccionar varios meses
             * - expanded = true:
             *   - Renderiza checkboxes
             *
             * NOTA:
             * - El JS reenvuelve estos checkboxes
             *   para mostrar etiquetas visuales por mes
             * =============================================
             */
            ->add('periodo', ChoiceType::class, [
                'choices' => [
                    'Ene' => 'Enero',
                    'Feb' => 'Febrero',
                    'Mar' => 'Marzo',
                    'Abr' => 'Abril',
                    'May' => 'Mayo',
                    'Jun' => 'Junio',
                    'Jul' => 'Julio',
                    'Ago' => 'Agosto',
                    'Sep' => 'Septiembre',
                    'Oct' => 'Octubre',
                    'Nov' => 'Noviembre',
                    'Dic' => 'Diciembre',
                ],
                'multiple' => true,
                'expanded' => true, // checkboxes visibles
            ]);
    }

    /**
     * =====================================================
     * CONFIGURACIÓN DEL FORM TYPE
     * -----------------------------------------------------
     * - Se asocia este formulario con la entidad Acciones
     * =====================================================
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Acciones::class,
        ]);
    }
}
