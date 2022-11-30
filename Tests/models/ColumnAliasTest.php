<?php
class ColumnAliasTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('column1 as alias1', 'string', 200);
        $this->hasColumn('column2 as alias2', 'integer', 4);
        $this->hasColumn('another_column as anotherField', 'string', 50);
        $this->hasColumn('book_id as bookId', 'integer', 4);
    }
    public function setUp(): void
    {
        $this->hasOne('Book as book', ['local' => 'book_id', 'foreign' => 'id']);
    }
}
