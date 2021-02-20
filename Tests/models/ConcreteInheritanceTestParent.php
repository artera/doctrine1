<?php
class ConcreteInheritanceTestParent extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string');
    }
}

class ConcreteInheritanceTestChild extends ConcreteInheritanceTestParent
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('age', 'integer');

        parent::setTableDefinition();
    }
}
