<?php

namespace App\Form;

use App\Entity\Encabezado;
use App\Entity\Indicadores;
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
 * FORM TYPE: INDICADORES
 * ---------------------------------------------------------
 * Define la estructura de un INDICADOR dentro del PTA.
 *
 * IMPORTANTE:
 * - Este FormType NO se renderiza directamente en la vista
 * - Se usa únicamente como:
 *   CollectionType + prototype
 * - El JS se encarga de:
 *   - Insertar filas
 *   - Asignar índices
 *   - Validar antes del submit
 * =========================================================
 */
class IndicadoresType extends AbstractType
{
    /**
     * =====================================================
     * DEFINICIÓN DEL FORMULARIO DE INDICADOR
     * =====================================================
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            /**
             * =============================================
             * DESCRIPCIÓN DEL INDICADOR
             * ---------------------------------------------
             * - Texto descriptivo del indicador
             * - Textarea para permitir texto largo
             * - Clase indicator-textarea:
             *   - Controla tamaño y comportamiento visual
             * =============================================
             */
            ->add('indicador', TextareaType::class, [
                'attr' => [
                    'rows' => 3,
                    'class' => 'indicator-textarea',
                    'placeholder' => 'Descripción del indicador'
                ],
            ])

            /**
             * =============================================
             * ÍNDICE LÓGICO DEL INDICADOR
             * ---------------------------------------------
             * - Campo hidden
             * - NO es ID de base de datos
             * - Se usa para:
             *   - Relacionar indicadores ↔ acciones en JS
             * - El valor se asigna dinámicamente en frontend
             * =============================================
             */
            ->add('indice', HiddenType::class)

            /**
             * =============================================
             * FÓRMULA DEL INDICADOR
             * ---------------------------------------------
             * - Describe cómo se calcula el indicador
             * - Textarea por consistencia visual
             * =============================================
             */
            ->add('formula', TextareaType::class, [
                'attr' => [
                    'rows' => 3,
                    'class' => 'indicator-textarea',
                    'placeholder' => 'Fórmula'
                ],
            ])

            /**
             * =============================================
             * VALOR A ALCANZAR (META)
             * ---------------------------------------------
             * - Campo numérico (tipo inferido desde entidad)
             * - Clase indicator-valor:
             *   - Controla ancho y alineación
             * =============================================
             */
            ->add('valor', null, [
                'attr' => [
                    'class' => 'indicator-valor',
                    'placeholder' => '0000'
                ],
            ])

            /**
             * =============================================
             * PERIODO DE MEDICIÓN
             * ---------------------------------------------
             * - Selección cerrada
             * - Valor por defecto: Anual
             * =============================================
             */
            ->add('periodo', ChoiceType::class, [
                'choices' => [
                    'Semestral' => 'Semestral',
                    'Anual' => 'Anual',
                ],
                'data' => 'Anual', // valor por defecto
            ])

            /**
             * =============================================
             * TENDENCIA DEL INDICADOR
             * ---------------------------------------------
             * - Define si el indicador debe:
             *   - Incrementar (POSITIVA)
             *   - Disminuir (NEGATIVA)
             * =============================================
             */
            ->add('tendencia', ChoiceType::class, [
                'choices'  => [
                    'POSITIVA' => 'POSITIVA',
                    'NEGATIVA' => 'NEGATIVA',
                ],
            ]);
    }

    /**
     * =====================================================
     * CONFIGURACIÓN DEL FORM TYPE
     * -----------------------------------------------------
     * - Se asocia este formulario con la entidad Indicadores
     * =====================================================
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Indicadores::class,
        ]);
    }
}
