<?php

namespace App\Form\ProcesoEstrategico;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ProcesoEstrategicoEditType extends ProcesoEstrategicoType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->add('activo', ChoiceType::class, [
            'label' => 'Estado',
            'choices' => [
                'Activo' => true,
                'Inactivo' => false,
            ],
            'expanded' => false,
            'multiple' => false,
        ]);
    }
}