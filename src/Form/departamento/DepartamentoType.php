<?php

namespace App\Form;
// Namespace del formulario dentro de la carpeta Form

use App\Entity\Departamento;
// Importa la entidad Departamento, que será el data_class del formulario

use Symfony\Component\Form\AbstractType;
// Clase base que deben extender todos los formularios de Symfony

use Symfony\Component\Form\FormBuilderInterface;
// Interfaz usada para construir el formulario

use Symfony\Component\OptionsResolver\OptionsResolver;
// Permite definir opciones del formulario


class DepartamentoType extends AbstractType
// Formulario base para CREAR un Departamento
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Método donde se definen los campos del formulario

        $builder
            ->add('nombre')
            // Agrega un campo de formulario llamado "nombre"
            // Symfony infiere automáticamente el tipo (TextType)
            // usando la metadata de la entidad Departamento
        ;
        // Fin de la definición del formulario
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Método para configurar opciones globales del formulario

        $resolver->setDefaults([
            'data_class' => Departamento::class,
            // Indica que este formulario está ligado a la entidad Departamento
            // Permite el mapeo automático formulario ↔ entidad
        ]);
    }
}
