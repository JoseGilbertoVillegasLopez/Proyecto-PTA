<?php

namespace App\Form;

use App\Entity\Acciones;
use App\Entity\Encabezado;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccionesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('accion')
            ->add('periodo')
            ->add('valorAlcanzado')
            ->add('encabezado', EntityType::class, [
                'class' => Encabezado::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Acciones::class,
        ]);
    }
}
