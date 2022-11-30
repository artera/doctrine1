<?php
class JC3 extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('c1_id', 'integer');
        $this->hasColumn('c2_id', 'integer');
    }
}
