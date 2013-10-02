<?php

namespace Pum\Core\Extension\Core\Type;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Pum\Core\AbstractType;
use Pum\Core\Context\FieldContext;
use Pum\Core\Validator\Constraints\Decimal;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata as ValidationClassMetadata;

class DecimalType extends AbstractType
{
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'unique'      => false,
            'precision'   => 18,
            'scale'       => 0,
            'label'       => null,
            'placeholder' => null
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildOptionsForm(FormBuilderInterface $builder)
    {
        $builder
            ->add('unique', 'checkbox', array('required' => false))
            ->add('precision', 'number', array('required' => false))
            ->add('scale', 'number', array('label' => 'Decimal', 'required' => false))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function mapDoctrineField(FieldContext $context, ClassMetadata $metadata)
    {
        $metadata->mapField(array(
            'columnName' => $context->getField()->getLowercaseName(),
            'fieldName' => $name,
            'type'      => 'decimal',
            'nullable'  => true,
            'unique'    => $context->getOption('unique'),
            'precision' => $context->getOption('precision'),
            'scale'     => $context->getOption('scale'),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function mapValidation(FieldContext $context, ValidationClassMetadata $metadata)
    {
        $metadata->addGetterConstraint($name, new Decimal());
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FieldContext $context, FormInterface $form)
    {
        $form->add($context->getField()->getLowercaseName(), 'text', array(
            'label' => $context->getOption('label'),
            'attr'  => array(
                    'placeholder' => $context->getOption('placeholder')
                )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'decimal';
    }
}
