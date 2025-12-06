<?php

namespace App\Form;

use App\Entity\Encabezado;
use App\Entity\Indicadores;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IndicadoresType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('indicador')
            ->add('formula')
            ->add('valor')
            ->add('periodo')
            ->add('encabezado', EntityType::class, [
                'class' => Encabezado::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Indicadores::class,
        ]);
    }
}
