<?php
class FooForeignlyOwned extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('fooId', 'integer');
    }
}
