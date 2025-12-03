<?php

namespace App\Form;

use App\Entity\Encabezado;
use App\Entity\Personal;
use App\Entity\Responsables;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EncabezadoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('objetivo')
            ->add('nombre')
            ->add('fechaCreacion')
            ->add('fechaConcluido')
            ->add('tendencia')
            ->add('status')
            ->add('responsable', EntityType::class, [
                'class' => Personal::class,
                'choice_label' => 'id',
            ])
            ->add('responsables', EntityType::class, [
                'class' => Responsables::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Encabezado::class,
        ]);
    }
}
