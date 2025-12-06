<?php

namespace App\Form;

use App\Entity\Encabezado;
use App\Entity\Personal;
use App\Entity\Responsables;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EncabezadoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
    ->add('objetivo')
    ->add('nombre')
    ->add('tendencia', ChoiceType::class, [
        'choices'  => [
            'Creciente' => true,
            'Decreciente' => false,
        ],
    ])
    ->add('responsable', EntityType::class, [
        'class' => Personal::class,
        'choice_label' => function (Personal $p) {
            return $p->__toString();
        },
    ])
    // SUBFORMULARIOS
    ->add('responsables', ResponsablesType::class)
    ->add('indicadores', CollectionType::class, [
        'entry_type' => IndicadoresType::class,
        'allow_add' => true,
        'allow_delete' => true,
        'by_reference' => false,
    ])
    ->add('acciones', CollectionType::class, [
        'entry_type' => AccionesType::class,
        'allow_add' => true,
        'allow_delete' => true,
        'by_reference' => false,
    ]);
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Encabezado::class,
        ]);
    }
}
