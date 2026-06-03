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
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;


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
                    'placeholder' => 'Escriba el indicador que corresponde'
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

            ->add('valorBase', NumberType::class, [
                'label' => 'Valor base',
                'scale' => 2,
                'attr' => [
                    'class' => 'indicator-valor',
                    'placeholder' => 'Ej. 700'
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
            ->add('periodo', HiddenType::class, [
                'data' => 'Anual',
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
                    'Positiva' => 'POSITIVA',
                    'Negativa' => 'NEGATIVA',
                ],
            ])
            /**
             * =============================================
             * META EXPRESADA COMO PORCENTAJE
             * ---------------------------------------------
             * El JS convierte este checkbox a un hidden con
             * value="1" (porcentaje) o value="0" (absoluto).
             *
             * false_values es OBLIGATORIO aquí:
             * Symfony trata cualquier campo presente en POST
             * como true, incluyendo value="0". Con false_values,
             * "0" y "" se mapean correctamente a false.
             * =============================================
             */
            ->add('esPorcentaje', CheckboxType::class, [
                'required'     => false,
                'false_values' => ['0', '', false],
                'attr'         => ['class' => 'es-porcentaje-hidden'],
            ])

            /**
             * =============================================
             * MODO DE CAPTURA MENSUAL (solo si esPorcentaje=true)
             * ---------------------------------------------
             * false → captura absoluta (misma unidad que valorBase)
             * true  → captura en porcentaje (0-100)
             *
             * Mismo problema que esPorcentaje: necesita false_values.
             * Además, el campo arranca en "" (vacío = no elegido aún),
             * que también debe mapearse a false.
             * =============================================
             */
            ->add('capturaEnPorcentaje', CheckboxType::class, [
                'required'     => false,
                'false_values' => ['0', '', false],
                'attr'         => ['class' => 'captura-pct-hidden'],
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
