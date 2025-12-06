<?php

namespace App\Form;

use App\Entity\Encabezado;
use App\Entity\Personal;
use App\Entity\Responsables;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EncabezadoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('objetivo', TextareaType::class, [
                'label' => 'Objetivo',
                'required' => true,
                'attr' => ['rows' => 3,
                'placeholder' => 'Escribe el objetivo aquí...'],
            ])
            ->add('nombre', TextType::class, [
                'label' => 'Nombre del proyecto',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Nombre del proyecto',
                    'autocomplete' => 'given-name' // para que el navegador no complete automaticamente
                ],
            ])
            ->add('tendencia', ChoiceType::class, [
                'label' => 'Tendencia',
                'choices' => [
                    'Positiva' => 'true',
                    'Negativa' => 'false',
                ],
                'placeholder' => 'Positiva es que incremetara un valor, Negativa es que disminuira un valor',
                'required' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Encabezado::class,
        ]);
    }
}
