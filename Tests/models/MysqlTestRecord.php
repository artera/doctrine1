<?php
class MysqlTestRecord extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', null, 'primary');
        $this->hasColumn('code', 'integer', null, 'primary');

        $this->getTable()->type = 'INNODB';
    }
}
