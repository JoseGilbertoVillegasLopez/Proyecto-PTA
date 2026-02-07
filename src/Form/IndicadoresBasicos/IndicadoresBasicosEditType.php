<?php

namespace App\Form\IndicadoresBasicos;


use App\Form\IndicadoresBasicosType;
use Symfony\Component\Form\FormBuilderInterface;

class IndicadoresBasicosEditType extends IndicadoresBasicosType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // ✅ Hereda todos los campos base
        parent::buildForm($builder, $options);

        // 🔒 De momento NO agregamos nada extra
        // Este EditType existe para:
        // - Escalabilidad futura
        // - Mantener el mismo patrón que Personal
        // - Evitar refactors después
    }
}
