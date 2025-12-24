<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ValorAlcanzadoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $meses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];

        foreach ($meses as $mes) {
            $builder->add($mes, NumberType::class, [
                'required' => false,
                'label' => $mes,
                'attr' => [
                    'placeholder' => 'Valor',
                    'class' => 'mes-input',
                    'min' => 0
                ]
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // SIN data_class → produce un array
        $resolver->setDefaults([
            'mapped' => true,
        ]);
    }
}
