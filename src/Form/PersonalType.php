<?php

namespace App\Form;

use App\Entity\Departamento;
use App\Entity\Personal;
use App\Entity\Puesto;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre')
            ->add('ap_paterno')
            ->add('ap_materno')
            ->add('correo')
            ->add('activo')
            ->add('puesto', EntityType::class, [
                'class' => Puesto::class,
                'choice_label' => 'id',
            ])
            ->add('departamento', EntityType::class, [
                'class' => Departamento::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personal::class,
        ]);
    }
}
