<?php
class ValidatorTest_DateModel extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('birthday', 'date', null, ['past']);
        $this->hasColumn('death', 'date', null, ['future']);
    }
}
