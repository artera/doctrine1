<?php
class File_Owner extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 255);
    }
    public function setUp(): void
    {
        $this->hasOne('Data_File', ['local' => 'id', 'foreign' => 'file_owner_id']);
    }
}
