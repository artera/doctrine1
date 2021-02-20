<?php
class GzipTest extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('gzip', 'gzip', 100000);
    }
}
