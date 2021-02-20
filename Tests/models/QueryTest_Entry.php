<?php
class QueryTest_Entry extends Doctrine_Record
{
    /**
     * Table structure.
     */
    public function setTableDefinition(): void
    {
        $this->hasColumn('id', 'integer', 4, ['primary', 'autoincrement', 'notnull']);
        $this->hasColumn(
            'authorId',
            'integer',
            4,
            ['notnull']
        );
        $this->hasColumn(
            'date',
            'integer',
            4,
            ['notnull']
        );
    }

    /**
     * Runtime definition of the relationships to other entities.
     */
    public function setUp(): void
    {
        $this->hasOne(
            'QueryTest_User as author',
            [
            'local' => 'authorId', 'foreign' => 'id'
            ]
        );
    }
}
