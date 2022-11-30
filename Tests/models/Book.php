<?php
class Book extends \Doctrine1\Record
{
    public function setUp(): void
    {
        $this->hasMany('Author', ['local' => 'id', 'foreign' => 'book_id']);
        $this->hasOne(
            'User',
            ['local'    => 'user_id',
                                    'foreign'  => 'id',
            'onDelete' => 'CASCADE']
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('name', 'string', 20);
    }
}
