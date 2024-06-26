<?php
class MysqlIndexTestRecord extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', null);
        $this->hasColumn('code', 'integer', 4);
        $this->hasColumn('content', 'string', 4000);

        $this->index('content', ['fields' => ['content'], 'type' => 'fulltext']);
        $this->index(
            'namecode',
            ['fields' => ['name', 'code'],
            'type'   => 'unique']
        );

        $this->getTable()->type = 'MYISAM';
    }
}
