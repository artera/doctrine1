<?php
class Data_File extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('filename', 'string');
        $this->hasColumn('file_owner_id', 'integer');
    }
    public function setUp(): void
    {
        $this->hasOne('File_Owner', ['local' => 'file_owner_id', 'foreign' => 'id']);
    }
}
