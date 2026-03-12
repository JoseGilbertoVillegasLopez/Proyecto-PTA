<?php

namespace App\Form\ProcesoClave;

use App\Entity\ProcesoClave;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProcesoClaveType extends AbstractType
{
    // FORM BASE - CREAR
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('nombre', TextType::class, [
                'label' => 'Nombre del Proceso',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ej. Gestión Académica',
                ],
            ])

            ->add('pei', TextType::class, [
                'label' => 'PEI',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ej. Objetivo estratégico PEI',
                ],
            ])

            ->add('paig', TextType::class, [
                'label' => 'PAIG',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ej. Acción institucional PAIG',
                ],
            ])

            ->add('metaPdiPta', TextType::class, [
                'label' => 'Meta PDI / PTA',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ej. Incrementar eficiencia 10%',
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProcesoClave::class,
        ]);
    }
}