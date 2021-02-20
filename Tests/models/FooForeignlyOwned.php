<?php
class FooForeignlyOwned extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('fooId', 'integer');
    }
}
