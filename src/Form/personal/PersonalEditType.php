<?php

namespace App\Form\personal;

use App\Form\PersonalType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PersonalEditType extends PersonalType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // ✅ Hereda todos los campos base
        parent::buildForm($builder, $options);

        // ✅ SOLO EN EDIT: Estado (activo / inactivo)
        $builder->add('activo', ChoiceType::class, [
            'label' => 'Estado',
            'choices' => [
                'Activo' => true,
                'Inactivo' => false,
            ],
            'expanded' => false, // select
            'multiple' => false,
        ]);
    }
}
