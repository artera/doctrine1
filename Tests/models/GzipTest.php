<?php
class GzipTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('gzip', 'gzip', 100000);
    }
}
