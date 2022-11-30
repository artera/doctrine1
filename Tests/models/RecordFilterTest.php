<?php
class RecordFilterTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 200);
        $this->hasColumn('password', 'string', 32);
    }

    public function setPassword($value, $load, $fieldName)
    {
        $this->set($fieldName, md5($value), $load);
    }

    public function getName($load, $fieldName)
    {
        return strtoupper($this->get($fieldName, $load));
    }
}
