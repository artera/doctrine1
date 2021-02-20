<?php
class BarRecord extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->setTableName('bar');
        $this->hasColumn('name', 'string', 200);
    }
    public function setUp(): void
    {
        $this->hasMany('FooRecord as Foo', ['local' => 'barId', 'foreign' => 'fooId', 'refClass' => 'FooBarRecord']);
    }
}
