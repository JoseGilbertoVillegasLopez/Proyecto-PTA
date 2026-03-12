<?php

namespace App\Form\PartidasPresupuestales;

use App\Entity\PartidasPresupuestales;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PartidasPresupuestalesType extends AbstractType
{
    // FORM BASE - CREAR
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

           ->add('capitulo', IntegerType::class, [
                'label' => 'Capítulo',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ej. 2000',
                    'min' => 0,
                    'step' => 1,
                ],
            ])

            ->add('partida', IntegerType::class, [
                'label' => 'Partida',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ej. 22101',
                    'min' => 0,
                    'step' => 1,
                ],
            ])

            ->add('descripcion', TextType::class, [
                'label' => 'Descripción',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ej. Material de oficina',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PartidasPresupuestales::class,
        ]);
    }
}