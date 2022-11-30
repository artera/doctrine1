<?php
class Description extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('description', 'string', 3000);
        $this->hasColumn('file_md5', 'string', 32);
    }
}
