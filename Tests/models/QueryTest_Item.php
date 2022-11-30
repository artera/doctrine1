<?php
class QueryTest_Item extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('price', 'decimal');
        $this->hasColumn('quantity', 'integer');
    }
}
