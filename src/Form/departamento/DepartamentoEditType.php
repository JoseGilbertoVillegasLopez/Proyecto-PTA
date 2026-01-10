<?php

namespace App\Form\departamento;

use App\Form\DepartamentoType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class DepartamentoEditType extends DepartamentoType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // hereda campos base
        parent::buildForm($builder, $options);

        // SOLO EN EDIT
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
