<?php

namespace Pum\Core\Type;

use Pum\Core\Extension\EmFactory\Doctrine\Metadata\ObjectClassMetadata;

class DateType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function mapDoctrineFields(ObjectClassMetadata $metadata, $name, array $options)
    {
        $unique    = isset($options['unique']) ? $options['unique'] : false;

        $metadata->mapField(array(
            'fieldName' => $name,
            'type'      => 'date',
            'nullable'  => true,
            'unique'    => $unique,
        ));
    }

    public function getFormOptionsType()
    {
        return 'ww_field_type_date';
    }
}
