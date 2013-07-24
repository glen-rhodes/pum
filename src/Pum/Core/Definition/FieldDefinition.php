<?php

namespace Pum\Core\Definition;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;

/**
 * Definition of a dynamic object.
 */
class FieldDefinition
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var ObjectDefinition
     */
    protected $object;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $typeOptions;

    /**
     * Constructor.
     */
    public function __construct($name = null, $type = null, $typeOptions = array())
    {
        $this->name        = $name;
        $this->type        = $type;
        $this->typeOptions = $typeOptions;
    }

    /**
     * @return ObjectDefinition
     */
    public static function create($name = null, $type = null, array $typeOptions = array())
    {
        return new self($name, $type, $typeOptions);
    }

    /**
     * @return Object
     */
    public function getObject()
    {
        return $this->name;
    }

    /**
     * Changes associated object.
     *
     * @return ObjectDefinition
     */
    public function setObject(ObjectDefinition $object = null)
    {
        $this->object = $object;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return ObjectField
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function getTypeOptions()
    {
        return $this->typeOptions;
    }

    /**
     * @return array
     */
    public function getTypeOption($name, $default = null)
    {
        return isset($this->typeOptions[$name]) ? $this->typeOptions[$name] : $default;
    }

    /**
     * @return FieldDefinition
     */
    public function setTypeOption($name, $value)
    {
        $this->typeOptions[$name] = $value;

        return $this;
    }

    /**
     * @return ObjectField
     */
    public function setTypeOptions(array $typeOptions)
    {
        $this->typeOptions = $typeOptions;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return ObjectField
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    public function getMetadataDefinition()
    {
        return array(
            'fieldName' => $this->getName(),
            'type'      => $this->getType(),
        );
    }
}
