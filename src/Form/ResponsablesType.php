<?php

namespace App\Form;

use App\Entity\Encabezado;
use App\Entity\Personal;
use App\Entity\Responsables;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResponsablesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // SUPERVISOR (ID real, NO mapeado)
            ->add('supervisor', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])

            // input visible
            ->add('supervisor_search', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Supervisor',
                'attr' => [
                    'class' => 'form-control supervisor-search',
                    'placeholder' => 'Buscar supervisor...',
                    'autocomplete' => 'off',
                ],
            ])

            // AVAL (ID real, NO mapeado)
            ->add('aval', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])

            // input visible
            ->add('aval_search', TextType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Aval',
                'attr' => [
                    'class' => 'form-control aval-search',
                    'placeholder' => 'Buscar aval...',
                    'autocomplete' => 'off',
                ],
            ])


        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Responsables::class,
        ]);
    }
}
