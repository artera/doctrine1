<?php
class Description extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('description', 'string', 3000);
        $this->hasColumn('file_md5', 'string', 32);
    }
}
