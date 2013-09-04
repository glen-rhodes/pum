<?php

namespace Pum\Bundle\WoodworkBundle\Form\TypeExtension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Extends form and give opportunity to pass block prefixes as options.
 */
class WoodworkConfigTypeExtension extends AbstractTypeExtension
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->get('tabs')
                ->add($builder->create('woodwork', 'pum_tab')
                    /*->add('ww_logo', 'pum_media', array(
                        'label'     => 'Woodwork Logo',
                        'show_name' => false
                    ))*/
                    ->add('ww_show_export_import_button', 'checkbox', array(
                        'label'    => 'Show export/import button',
                    ))
                    ->add('ww_show_clone_button', 'checkbox', array(
                        'label'    => 'Show clone button'
                    ))
                )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'pum_config';
    }
}
