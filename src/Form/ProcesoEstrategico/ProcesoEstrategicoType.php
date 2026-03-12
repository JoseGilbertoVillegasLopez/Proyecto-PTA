<?php

namespace App\Form\ProcesoEstrategico;

use App\Entity\ProcesoEstrategico;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProcesoEstrategicoType extends AbstractType
{
    // FORM BASE - CREAR
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('nombre', TextType::class, [
                'label' => 'Nombre del Proceso Estratégico',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ej. Planeación Institucional',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProcesoEstrategico::class,
        ]);
    }
}