<?php

namespace App\Form;

use App\Entity\Puesto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class PuestoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre')

            ->add('supervisorDirecto', EntityType::class, [
                'class' => Puesto::class,
                'choice_label' => 'nombre',
                'required' => false,
                'label' => 'Supervisor directo',
                'placeholder' => '— Sin supervisor —',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Puesto::class,
        ]);
    }
}
