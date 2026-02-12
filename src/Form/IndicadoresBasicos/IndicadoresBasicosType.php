<?php

namespace App\Form\IndicadoresBasicos;

use App\Entity\IndicadoresBasicos;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IndicadoresBasicosType extends AbstractType
{
    // FORM BASE - CREAR
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('nombreIndicador', TextType::class, [
                'label' => 'Nombre del Indicador',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ej. % Atención a la demanda',
                ],
            ])

            ->add('formula', TextType::class, [
                'label' => 'Fórmula',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ej. (Atendidos / Solicitados) * 100',
                ],
            ])

            ->add('observaciones', TextareaType::class, [
                'label' => 'Observaciones',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Notas adicionales del indicador',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IndicadoresBasicos::class,
        ]);
    }
}