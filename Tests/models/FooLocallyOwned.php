<?php
class FooLocallyOwned extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 200);
    }
}
