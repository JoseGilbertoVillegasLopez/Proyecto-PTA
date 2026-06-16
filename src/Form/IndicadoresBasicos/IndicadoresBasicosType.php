<?php

namespace App\Form\IndicadoresBasicos;

use App\Entity\Departamento;
use App\Entity\GrupoIndicadoresBasicos;
use App\Entity\IndicadoresBasicos;
use App\Repository\DepartamentoRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IndicadoresBasicosType extends AbstractType
{
    // FORM BASE - CREAR
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('nombreIndicador', TextType::class, [
                'label' => 'Nombre del Indicador',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ej. % Atención a la demanda',
                ],
            ])

            ->add('formula', TextType::class, [
                'label' => 'Fórmula',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Ej. (Atendidos / Solicitados) * 100',
                ],
            ])

            ->add('observaciones', TextareaType::class, [
                'label' => 'Observaciones',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Notas adicionales del indicador',
                ],
            ])

            ->add('grupo', EntityType::class, [
                'class' => GrupoIndicadoresBasicos::class,
                'choice_label' => 'grupo',
                'label' => 'Grupo',
                'placeholder' => '— Sin grupo —',
                'required' => false,
                'attr' => [
                    'class' => 'indicadores-select-pta',
                ],
            ])

            ->add('departamentos', EntityType::class, [
                'class' => Departamento::class,
                'choice_label' => 'nombre',
                'label' => 'Departamentos responsables',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'by_reference' => false,
                'query_builder' => function (DepartamentoRepository $repo) {
                    return $repo->createQueryBuilder('d')
                        ->where('d.activo = :activo')
                        ->setParameter('activo', true)
                        ->orderBy('d.nombre', 'ASC');
                },
                'attr' => [
                    'class' => 'indicadores-basicos-new-select',
                    'size' => 6,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => IndicadoresBasicos::class,
        ]);
    }
}