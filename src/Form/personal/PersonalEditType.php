<?php
namespace App\Form\personal;
use App\Form\PersonalType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class PersonalEditType extends PersonalType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('correo')
        ->add('puesto', EntityType::class, [
            'class' => 'App\Entity\Puesto',
            'choice_label' => 'nombre',
            'label' => 'Puesto',
        ])
        ->add('departamento', EntityType::class, [
            'class' => 'App\Entity\Departamento',
            'choice_label' => 'nombre',
            'label' => 'Departamento',
        ])
        ;

        
    }
}