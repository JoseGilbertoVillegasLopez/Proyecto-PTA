<?php

namespace App\Form;

use App\Entity\Encabezado;
use App\Entity\Indicadores;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IndicadoresType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
    ->add('indicador', TextType::class)
    ->add('formula', TextType::class)
    ->add('valor', TextType::class)
    ->add('periodo', ChoiceType::class, [
        'choices' => [
            'Semestral' => 'Semestral',
            'Anual' => 'Anual',
        ],
        'data' => 'Anual', // valor por defecto
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Indicadores::class,
        ]);
    }
}
