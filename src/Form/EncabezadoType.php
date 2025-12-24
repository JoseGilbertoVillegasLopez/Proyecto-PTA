<?php

namespace App\Form;

use App\Entity\Encabezado;
use App\Entity\Personal;
use App\Entity\Responsables;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * =========================================================
 * FORM TYPE: ENCABEZADO
 * ---------------------------------------------------------
 * Este FormType define la estructura base del formulario PTA.
 *
 * IMPORTANTE:
 * - Aquí NO se valida lógica de negocio compleja
 * - La validación fuerte ocurre en:
 *   - JavaScript (frontend)
 *   - Controller (backend)
 * =========================================================
 */
class EncabezadoType extends AbstractType
{
    /**
     * =====================================================
     * DEFINICIÓN DEL FORMULARIO
     * -----------------------------------------------------
     * Se agregan los campos principales del PTA y
     * los subformularios relacionados.
     * =====================================================
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            /**
             * =============================================
             * AÑO DE EJECUCIÓN DEL PTA
             * ---------------------------------------------
             * - Representa el periodo real del PTA
             * - Solo permite:
             *   - Año actual
             *   - Año siguiente
             * =============================================
             */
            ->add('anioEjecucion', ChoiceType::class, [
                'label' => 'Año de ejecución',
                'choices' => [
                    date('Y') => date('Y'),
                    date('Y') + 1 => date('Y') + 1,
                ],
                'placeholder' => 'Seleccione el año de ejecución',
            ])


            /**
             * =============================================
             * OBJETIVO DEL PROYECTO
             * ---------------------------------------------
             * - Textarea para permitir texto largo
             * - Clase fixed-textarea:
             *   - Controla el auto-grow vía JS
             * =============================================
             */
            ->add('objetivo', TextareaType::class, [
                'label' => 'Objetivo del Proyecto',
                'attr' => [
                    'rows' => 3,
                    'class' => 'fixed-textarea',
                    'placeholder' => 'Objetivo del proyecto'
                ],
            ])

            /**
             * =============================================
             * NOMBRE DEL PROYECTO
             * ---------------------------------------------
             * - Textarea por consistencia visual
             * - Comparte comportamiento con "objetivo"
             * =============================================
             */
            ->add('nombre', TextareaType::class, [
                'label' => 'Nombre del Proyecto',
                'attr' => [
                    'rows' => 3,
                    'class' => 'fixed-textarea',
                    'placeholder' => 'Nombre del proyecto'
                ],
            ])

            /**
             * =============================================
             * RESPONSABLE PRINCIPAL
             * ---------------------------------------------
             * - Es el Personal asociado al usuario logueado
             * - Se usa EntityType porque:
             *   - Es una relación directa
             *   - NO es búsqueda dinámica
             *
             * NOTA:
             * - El valor se asigna automáticamente
             *   en el Controller (new)
             * =============================================
             */
            ->add('responsable', EntityType::class, [
                'class' => Personal::class,
                'choice_label' => function (Personal $p) {
                    return $p->__toString();
                },
            ])

            /**
             * =============================================
             * SUBFORMULARIO: RESPONSABLES
             * ---------------------------------------------
             * Incluye:
             * - Supervisor
             * - Aval
             *
             * IMPORTANTE:
             * - Sus campos son mapped=false
             * - La asignación real se hace en el Controller
             * =============================================
             */
            ->add('responsables', ResponsablesType::class)

            /**
             * =============================================
             * COLECCIÓN DE INDICADORES
             * ---------------------------------------------
             * - CollectionType dinámico
             * - Se maneja con JS + prototype
             *
             * by_reference = false:
             * - Obliga a Doctrine a usar add/remove
             * - Necesario para relaciones OneToMany
             * =============================================
             */
            ->add('indicadores', CollectionType::class, [
                'entry_type' => IndicadoresType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ])

            /**
             * =============================================
             * COLECCIÓN DE ACCIONES
             * ---------------------------------------------
             * - También dinámica
             * - Relacionada lógicamente con Indicadores
             *   mediante un índice (NO relación directa)
             * =============================================
             */
            ->add('acciones', CollectionType::class, [
                'entry_type' => AccionesType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
            ]);
    }

    /**
     * =====================================================
     * CONFIGURACIÓN DEL FORM TYPE
     * -----------------------------------------------------
     * - Se indica la entidad base que mapea el formulario
     * =====================================================
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Encabezado::class,
        ]);
    }
}
