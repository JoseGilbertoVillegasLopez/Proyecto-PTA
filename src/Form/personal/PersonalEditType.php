<?php

namespace App\Form\personal;

use App\Entity\Personal;
use App\Entity\Puesto;
use App\Entity\Departamento;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PersonalEditType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre', TextType::class)
            ->add('ap_paterno', TextType::class)
            ->add('ap_materno', TextType::class)
            ->add('correo', EmailType::class)
            ->add('puesto', EntityType::class, [
                'class' => Puesto::class,
                'choice_label' => 'nombre',
            ])
            ->add('departamento', EntityType::class, [
                'class' => Departamento::class,
                'choice_label' => 'nombre',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Personal::class,
        ]);
    }
}
