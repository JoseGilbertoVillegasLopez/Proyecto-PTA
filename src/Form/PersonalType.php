<?php

namespace App\Form;

use App\Entity\Departamento;
use App\Entity\Personal;
use App\Entity\Puesto;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonalType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class,[
                'required' => true,
                'label' => 'Nombre(s)',
                'attr' => [
                    'placeholder' => 'Nombre(s)',
                    'autocomplete' => 'given-name' // para que el navegador no complete automaticamente
                ]
            ])
            ->add('ap_paterno', TextType::class,[
                'required' => true,
                'label' => 'Apellido Paterno',
                'attr' => [
                    'placeholder' => 'Apellido Paterno',
                    'autocomplete' => 'family-name' // para que el navegador no complete automaticamente
                ]
            ])
            ->add('ap_materno', TextType::class,[
                'required' => true,
                'label' => 'Apellido Materno',
                'attr' => [
                    'placeholder' => 'Apellido Materno',
                    'autocomplete' => 'additional-name' // para que el navegador no complete automaticamente
                ]
            ])
            ->add('correo', EmailType::class,[
                'required' => true,
                'label' => 'Correo electrónico',
                'attr' => [
                    'autocomplete' => 'email'

                ]
            ])
            ->add('puesto', EntityType::class, [
                'class' => Puesto::class,
                'label' => 'Puesto',
                'choice_label' => 'nombre',
            ])
            ->add('departamento', EntityType::class, [
                'class' => Departamento::class,
                'label' => 'Departament',
                'choice_label' => 'nombre',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personal::class,
        ]);
    }
}
