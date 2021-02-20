<?php
class Author extends Doctrine_Record
{
    public function setUp(): void
    {
        $this->hasOne(
            'Book',
            ['local'    => 'book_id',
                                    'foreign'  => 'id',
            'onDelete' => 'CASCADE']
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('book_id', 'integer');
        $this->hasColumn('name', 'string', 20);
    }
}
