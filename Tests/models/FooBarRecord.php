<?php
class FooBarRecord extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('fooId', 'integer', null, ['primary' => true]);
        $this->hasColumn('barId', 'integer', null, ['primary' => true]);
    }
}
