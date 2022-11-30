<?php
class ZeroValueTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('id', 'integer', 4, ['primary' => true,  'autoincrement' => true,]);
        $this->hasColumn('username', 'string', 128, ['notnull' => true,]);
        $this->hasColumn('algorithm', 'string', 128, ['default' => 'sha1', 'notnull' => true,]);
        $this->hasColumn('salt', 'string', 128, ['notnull' => true,]);
        $this->hasColumn('password', 'string', 128, ['notnull' => true,]);
        $this->hasColumn('created_at', 'timestamp', null, []);
        $this->hasColumn('last_login', 'timestamp', null, []);
        $this->hasColumn('is_active', 'boolean', null, ['default' => true, 'notnull' => true,]);
        $this->hasColumn('is_super_admin', 'boolean', null, ['default' => false, 'notnull' => true,]);
    }

    public function setUp(): void
    {
    }
}
