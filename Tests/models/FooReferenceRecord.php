<?php
class FooReferenceRecord extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->setTableName('foo_reference');

        $this->hasColumn('foo1', 'integer', null, ['primary' => true]);
        $this->hasColumn('foo2', 'integer', null, ['primary' => true]);
    }
}
