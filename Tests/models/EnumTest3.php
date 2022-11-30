<?php
class EnumTest3 extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('text', 'string', 10, ['primary' => true]);
    }
}
