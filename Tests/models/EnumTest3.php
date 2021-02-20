<?php
class EnumTest3 extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('text', 'string', 10, ['primary' => true]);
    }
}
