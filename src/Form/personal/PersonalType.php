<?php

namespace App\Form;
// Namespace del formulario dentro de la carpeta Form

use App\Entity\Departamento;
// Importa la entidad Departamento para usarla en campos EntityType

use App\Entity\Personal;
// Importa la entidad Personal, que será el data_class del formulario

use App\Entity\Puesto;
// Importa la entidad Puesto para el selector de puestos

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
// Tipo de campo de Symfony que permite seleccionar entidades desde Doctrine

use Symfony\Component\Form\AbstractType;
// Clase base que deben extender todos los formularios de Symfony

use Symfony\Component\Form\Extension\Core\Type\EmailType;
// Tipo de campo específico para correos electrónicos

use Symfony\Component\Form\Extension\Core\Type\TextType;
// Tipo de campo de texto simple

use Symfony\Component\Form\FormBuilderInterface;
// Interfaz que permite construir el formulario campo por campo

use Symfony\Component\OptionsResolver\OptionsResolver;
// Permite configurar opciones del formulario (como data_class)



class PersonalType extends AbstractType
// Formulario base para CREAR un registro de Personal
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Método donde se definen todos los campos del formulario

        $builder
            ->add('nombre', TextType::class,[
                // Campo de texto ligado a la propiedad "nombre" de la entidad Personal

                'required' => true,
                // El campo es obligatorio

                'label' => 'Nombre(s)',
                // Etiqueta mostrada en la vista

                'attr' => [
                    // Atributos HTML del input

                    'placeholder' => 'Nombre(s)',
                    // Texto guía dentro del input

                    'autocomplete' => 'given-name'
                    // para que el navegador no complete automaticamente
                ]
            ])

            ->add('ap_paterno', TextType::class,[
                // Campo de texto para el apellido paterno

                'required' => true,
                // Campo obligatorio

                'label' => 'Apellido Paterno',
                // Etiqueta visible

                'attr' => [
                    'placeholder' => 'Apellido Paterno',
                    // Placeholder del input

                    'autocomplete' => 'family-name'
                    // para que el navegador no complete automaticamente
                ]
            ])

            ->add('ap_materno', TextType::class,[
                // Campo de texto para el apellido materno

                'required' => true,
                // Campo obligatorio

                'label' => 'Apellido Materno',
                // Etiqueta visible

                'attr' => [
                    'placeholder' => 'Apellido Materno',
                    // Placeholder del input

                    'autocomplete' => 'additional-name'
                    // para que el navegador no complete automaticamente
                ]
            ])

            ->add('correo', EmailType::class,[
                // Campo de tipo email ligado a la propiedad "correo"

                'required' => true,
                // Campo obligatorio

                'label' => 'Correo electrónico',
                // Etiqueta mostrada en la vista

                'attr' => [
                    'autocomplete' => 'email'
                    // Permite al navegador sugerir correos guardados
                ]
            ])

            ->add('puesto', EntityType::class, [
                // Campo tipo select que carga entidades Puesto desde la BD

                'class' => Puesto::class,
                // Entidad que se va a consultar

                'label' => 'Puesto',
                // Etiqueta visible del campo

                'choice_label' => 'nombre',
                // Propiedad del Puesto que se mostrará en el select

                'query_builder' => function (\App\Repository\PuestoRepository $repo) {
                    // Callback que define la consulta personalizada

                    return $repo->createQueryBuilder('p')
                        // Crea el QueryBuilder con alias "p"

                        ->where('p.activo = :activo')
                        // Filtra solo puestos activos

                        ->setParameter('activo', true)
                        // Asigna el valor del parámetro :activo

                        ->orderBy('p.nombre', 'ASC');
                        // Ordena los puestos alfabéticamente
                },
            ])

            ->add('departamento', EntityType::class, [
                // Campo tipo select que carga entidades Departamento

                'class' => Departamento::class,
                // Entidad usada para el select

                'label' => 'Departamento',
                // Etiqueta visible

                'choice_label' => 'nombre',
                // Propiedad mostrada en el select

                'query_builder' => function (\App\Repository\DepartamentoRepository $repo) {
                    // Callback para personalizar la consulta

                    return $repo->createQueryBuilder('d')
                        // QueryBuilder con alias "d"

                        ->where('d.activo = :activo')
                        // Filtra solo departamentos activos

                        ->setParameter('activo', true)
                        // Valor del parámetro

                        ->orderBy('d.nombre', 'ASC');
                        // Ordena alfabéticamente
                },
            ])

        ;
        // Fin de la definición del formulario
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        // Método donde se configuran opciones globales del formulario

        $resolver->setDefaults([
            'data_class' => Personal::class,
            // Indica que este formulario está ligado a la entidad Personal
            // Symfony hará el mapeo automático de campos ↔ propiedades
        ]);
    }
}
