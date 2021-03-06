<?php

namespace Pum\Core\Extension\ProjectAdmin\Form\Type;

use Symfony\Bridge\Doctrine\Form\ChoiceList\EntityChoiceList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PumAjaxObjectEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options) {
            $form   = $event->getForm();
            $data   = $event->getData();
            $em     = $options['em'];

            $update = false;
            $parent = $form->getParent();

            while (null !== $parent->getParent()) {
                $parent = $parent->getParent();
            }

            if (null !== $object = $parent->getData()) {
                if ($parent->has($options['field_name'])) {

                    if (!$options['multiple']) {
                        if (null === $data) {
                            $new = null;
                        } else {
                            $new = $em->getRepository($options['target'])->find((int)$data);
                        }

                        $setter = 'set'.ucfirst($options['field_name']);
                        $object->$setter($new);

                        $event->setData($new);
                    } else {

                        if (substr($options['field_name'], -1) === 's') {
                            $singular = substr($options['field_name'], 0, -1);
                        } else {
                            $singular = $options['field_name'];
                        }

                        $data     = (array) $data;
                        $getter   = 'get'.ucfirst($options['field_name']);
                        $adder    = 'add'.ucfirst($singular);
                        $remover  = 'remove'.ucfirst($singular);
                        $children = $object->$getter();

                        foreach ($children as $key => $child) {
                            if (!in_array($child->getId(), $data)) {
                                $object->$remover($child);
                            } elseif (($key = array_search($child->getId(), $data)) !== false) {
                                unset($data[$key]);
                            }
                        }

                        foreach ($data as $key => $id) {
                            if (null !== $new = $em->getRepository($options['target'])->find($id)) {
                                $object->$adder($new);
                            }
                        }

                        $event->setData($object->$getter());
                    }

                    $update = true;
                }
            }

            if (!$update) {
                throw new \RuntimeException(sprintf('Unable to update field "%s"', $options['field_name']));
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options) {
            $form   = $event->getForm();
            $data   = $event->getData();
            $em     = $options['em'];

            $update = false;
            $parent = $form->getParent();

            while (null !== $parent->getParent()) {
                $parent = $parent->getParent();
            }

            if (null !== $object = $parent->getData()) {
                if ($parent->has($options['field_name'])) {
                    if ($parent->isValid()) {
                        $parent->remove($options['field_name']);
                    }
                }
            }
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['allow_add']     = $options['allow_add'];
        $view->vars['allow_select']  = $options['allow_select'];
        $view->vars['target']        = $options['target'];
        $view->vars['field_name']    = $options['field_name'];
        $view->vars['property_name'] = $options['property_name'];
        $view->vars['ajax']          = $options['ajax'];
        $view->vars['ajaxsearch']    = true;
        $view->vars['ids_delimiter'] = $options['ids_delimiter'];
        

        if ($options['ajax']) {
            $key = $view->vars['name'];
            $parent = $view;
            while ($parent = $parent->parent) {
                if ($parent->parent === null) {
                    break;
                }
                $key = $parent->vars['name'].'.'.$key;
            }
            $view->vars['ajax_id'] = $key;
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        // disable EM normalizer
        $resolver->setNormalizers(array('em' => function (Options $options, $val) { return $val; }));

        $resolver->setDefaults(array(
            'target' => function (Options $options) {
                return $options['target'];
            },
            'ids_delimiter' => function (Options $options) {
                return $options['ids_delimiter'];
            },
            'field_name' => function (Options $options) {
                return $options['field_name'];
            },
            'property_name' => function (Options $options) {
                return $options['property_name'];
            },
            'multiple' => function (Options $options) {
                return $options['multiple'];
            },
            'query_builder' => function ($repo) {
                return $repo->createQueryBuilder('o')
                    ->setMaxResults(0)
                ;
            }
        ));
    }

    public function getParent()
    {
        return 'pum_object_entity';
    }

    public function getName()
    {
        return 'pum_ajax_object_entity';
    }
}
