<?php

namespace Pum\Core\Definition\View;

use Doctrine\Common\Collections\ArrayCollection;
use Pum\Core\Definition\FieldDefinition;
use Pum\Core\Definition\ObjectDefinition;
use Pum\Core\Definition\View\FormViewField;
use Pum\Core\Exception\DefinitionNotFoundException;

class FormView extends AbstractView
{
    const VIEW_TYPE = 'formview';
    const DEFAULT_NAME = 'Default';

    const TYPE_CREATE = 0;
    const TYPE_EDIT = 1;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var ObjectDefinition
     */
    protected $objectDefinition;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var boolean
     */
    protected $private;

    /**
     * @var ArrayCollection
     */
    protected $fields;

    /**
     * @var boolean
     */
    private $default = false;

    /**
     * @var integer
     */
    private $type;

    /**
     * @param ObjectDefinition $objectDefinition
     * @param string $name name of the form view.
     */
    public function __construct(ObjectDefinition $objectDefinition = null, $name = null)
    {
        $this->objectDefinition  = $objectDefinition;
        $this->name    = $name;
        $this->private = false;
        $this->fields  = new ArrayCollection();
    }

    /**
     * @return ObjectDefinition
     */
    public function getObjectDefinition()
    {
        return $this->objectDefinition;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return FormView
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getPrivate()
    {
        return $this->private;
    }

    /**
     * @return FormView
     */
    public function setPrivate($private)
    {
        $this->private = (boolean)$private;

        return $this;
    }

    /**
     * Get default
     *
     * @return boolean
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set default
     *
     * @param boolean $default
     * @return FormView
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set type
     *
     * @param integer $type
     * @return FormView
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return FormView
     */
    public function setView(FormViewNode $view = null)
    {
        $this->view = $view;

        return $this;
    }

    /**
     * @return FormView
     */
    public function createRootViewNode()
    {
        $formViewNode = new FormViewNode();
        $formViewNode
            ->setName(FormViewNode::TYPE_ROOT)
            ->setType(FormViewNode::TYPE_ROOT)
        ;

        $this->setView($formViewNode);

        return $this;
    }

    /**
     * @return FormView
     */
    public function removeField(FormViewField $field)
    {
        $this->getFields()->removeElement($field);

        return $this;
    }

    /**
     * @return FormView
     */
    public function removeFields()
    {
        $this->getFields()->clear();

        return $this;
    }

    /**
     * Returns the field mapped by a given label.
     *
     * @return mixed
     * @throws DefinitionNotFoundException
     */
    public function getField($label)
    {
        foreach ($this->getFields() as $field) {
            if ($label instanceof FormViewField && $field === $label) {
                return $field;
            } elseif ($label instanceof FieldDefinition && $field->getField() === $label) {
                return $field;
            } elseif (is_string($label) && $field->getLabel() === $label) {
                return $field;
            }
        }

        if ($label instanceof FieldDefinition) {
            $label = $label->getName();
        } elseif ($label instanceof FormViewField) {
            $label = $label->getLabel();
        }

        throw new DefinitionNotFoundException($label);
    }

    /**
     * @return ArrayCollection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @return boolean
     */
    public function hasField($label)
    {
        try {
            $this->getField($label);

            return true;
        } catch (DefinitionNotFoundException $e) {
            return false;
        }
    }

    /**
     * @return FormView
     */
    public function addField(FormViewField $field)
    {
        $this->getFields()->add($field);
        $field->setFormView($this);

        return $this;
    }

    /**
     * @return FormView
     */
    public function createField($label, $field = null, $view = 'default', $sequence = null)
    {
        if (null === $field) {
            $field = $label;
        }

        if (is_string($label) && $this->getObjectDefinition()) {
            $field = $this->getObjectDefinition()->getField($label);
        }

        if (!$field instanceof FieldDefinition) {
            throw new \InvalidArgumentException('Expected a FieldDefinition, got a "%s".', is_object($field) ? get_class($field) : gettype($field));
        }

        $this->addField(new FormViewField($label, $field, $view, $sequence));

        return $this;
    }
}
