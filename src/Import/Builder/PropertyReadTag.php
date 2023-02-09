<?php

namespace Doctrine1\Import\Builder;

use Laminas\Code\Generator\DocBlock\Tag\PropertyTag;

class PropertyReadTag extends PropertyTag
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'property-read';
    }

    /**
     * @return string
     */
    public function generate()
    {
        return '@property-read'
            . (! empty($this->types) ? ' ' . $this->getTypesAsString() : '')
            . (! empty($this->propertyName) ? ' $' . $this->propertyName : '')
            . (! empty($this->description) ? ' ' . $this->description : '');
    }
}
