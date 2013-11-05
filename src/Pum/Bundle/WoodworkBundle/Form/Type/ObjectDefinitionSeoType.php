<?php

namespace Pum\Bundle\WoodworkBundle\Form\Type;

use Pum\Core\Definition\ObjectDefinition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\ObjectChoiceList;
use Symfony\Component\Finder\Finder;

class ObjectDefinitionSeoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // here, we're not able to use form events because form inherits data.
        // That's a limitation, because of:
        // https://github.com/symfony/symfony/issues/8607

        $fields = array();
        $objectDefinition = $options['objectDefinition'];
        foreach ($objectDefinition->getFields() as $field) {
            if ($field->getType() != 'relation') {
                $fields[] = $field;
            }
        }

        $builder
            ->add('seoEnabled', 'checkbox', array('label' => 'Activate SEO on this object'))
            ->add('seoField', 'entity', array(
                    'class'    => 'Pum\Core\Definition\FieldDefinition',
                    'choice_list' => new ObjectChoiceList($fields, 'name', array(), 'object.name', 'name')
                ))
            /*->add('seoField', 'entity', array('class' => 'Pum\Core\Definition\FieldDefinition', 'property' => 'name', 'group_by' => 'object.name'))*/
        ;

        if (null !== $options['bundlesName'] && null !== $options['rootDir']) {
            $templates = $this->getTemplates($options['rootDir'], $options['bundlesName']);
            $builder->add('seoTemplate', 'choice', array(
                'choices'     => array_combine($templates, $templates),
                'empty_value' => 'Choose a template',
            ));
        } else {
            $builder->add('seoTemplate', 'text');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'inherit_data' => true,
            'rootDir'      => null,
            'bundlesName'  => null
        ));

        $resolver->setRequired(array('objectDefinition'));
    }

    private function getTemplates($rootDir, $bundles)
    {
        $templates = array();
        $folders   = array();
        foreach ($bundles as $bundle => $class) {
            if (is_dir($dir = $rootDir.'/Resources/'.$bundle.'/pum_views')) {
                $folders[] = $dir;
            }

            $reflection = new \ReflectionClass($class);
            if (is_dir($dir = dirname($reflection->getFilename()).'/Resources/pum_views')) {
                $folders[] = $dir;
            }
        }

        $finder = new Finder();
        $finder->in($folders);
        $finder->files()->name('*.twig');
        $finder->files()->contains('{# root #}');

        foreach ($finder as $file) {
            $templates[] = 'pum://'.str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());
        }
        
        return $templates;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ww_object_definition_seo';
    }
}