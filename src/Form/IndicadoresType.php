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

class IndicadoresType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('indicador', TextareaType::class, [
            'attr' => [
                'rows' => 3,
                'class' => 'indicator-textarea',
                'placeholder' => 'Descripción del indicador'
            ],
        ])


        ->add('indice', HiddenType::class)

        ->add('formula', TextareaType::class, [
            'attr' => [
                'rows' => 3,
                'class' => 'indicator-textarea',
                'placeholder' => 'Fórmula'
            ],
        ])


        ->add('valor', null, [
            'attr' => [
                'class' => 'indicator-valor',
                'placeholder' => '0000'
            ],
        ])

    
    ->add('periodo', ChoiceType::class, [
        'choices' => [
            'Semestral' => 'Semestral',
            'Anual' => 'Anual',
        ],
        'data' => 'Anual', // valor por defecto
    ])
    ->add('tendencia', ChoiceType::class, [
        'choices'  => [
            'POSITIVA' => "POSITIVA",
            'NEGATIVA' => "NEGATIVA",
        ],
    ])
    ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Indicadores::class,
        ]);
    }
}
