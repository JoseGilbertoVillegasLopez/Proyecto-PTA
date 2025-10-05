<?php

namespace App\Form;

use App\Entity\Personal;
use App\Entity\Usuario;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UsuarioType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('personal') // por defecto es EntityType y toma por defecto el id, para que mostrara el nombre completo se agrego 
            // el metodo __toString en la entidad Personal y como por defecto symfony toma el -toString- no es 
            // necesario agregar mas opciones aqui
            ->add('usuario')
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'required' => true,                // en new: true; en edit puedes poner false
                'attr' => ['autocomplete' => 'new-password'], // para que el navegador no complete automaticamente
                'label' => 'Password',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Usuario::class,
        ]);
    }
}

	
