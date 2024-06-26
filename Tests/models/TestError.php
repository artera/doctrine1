<?php
class TestError extends \Doctrine1\Record
{
    public function setUp(): void
    {
        $this->hasMany(
            'Description',
            ['local'   => 'file_md5',
            'foreign' => 'file_md5']
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('message', 'string', 200);
        $this->hasColumn('code', 'integer', 11);
        $this->hasColumn('file_md5', 'string', 32, 'primary');
    }
}
