<?php

namespace App\Form;

use App\Entity\Encabezado;
use App\Entity\Personal;
use App\Entity\Responsables;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResponsablesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('encabezado', EntityType::class, [
                'class' => Encabezado::class,
                'choice_label' => 'id',
            ])
            ->add('supervisor', EntityType::class, [
                'class' => Personal::class,
                'choice_label' => 'id',
            ])
            ->add('aval', EntityType::class, [
                'class' => Personal::class,
                'choice_label' => 'id',
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
