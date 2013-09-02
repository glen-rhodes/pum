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
            /*->add($builder->create('tabs', 'ww_tabs')
                ->add($builder->create('informations', 'ww_tab')*/
                    ->add('ww_logo', 'file', array(
                        'label'    => 'Woodwork Logo'
                    ))
                    ->add('ww_show_export_import_button', 'checkbox', array(
                        'label'    => 'Show export/import button',
                    ))
                    ->add('ww_show_clone_button', 'checkbox', array(
                        'label'    => 'Show clone button'
                    ))
                /*)
            )*/
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
