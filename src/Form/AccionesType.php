<?php

namespace App\Form;

use App\Entity\Acciones;
use App\Entity\Encabezado;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AccionesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
    ->add('accion', TextType::class)

     // Campo técnico: índice del indicador
    ->add('indicador', HiddenType::class)

    
    ->add('periodo', ChoiceType::class, [
        'choices' => [
            'Ene' => 'Enero', 'Feb' => 'Febrero', 'Mar' => 'Marzo', 'Abr' => 'Abril', 'May' => 'Mayo',
            'Jun' => 'Junio', 'Jul' => 'Julio', 'Ago' => 'Agosto', 'Sep' => 'Septiembre',
            'Oct' => 'Octubre', 'Nov' => 'Noviembre', 'Dic' => 'Diciembre',
        ],
        'multiple' => true,
        'expanded' => true, // para usar los cuadritos pintados
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
