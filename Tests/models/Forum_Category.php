<?php
class Forum_Category extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('root_category_id', 'integer', 10);
        $this->hasColumn('parent_category_id', 'integer', 10);
        $this->hasColumn('name', 'string', 50);
        $this->hasColumn('description', 'string', 99999);
    }
    public function setUp(): void
    {
        $this->hasMany(
            'Forum_Category as Subcategory',
            [
            'local'   => 'id',
            'foreign' => 'parent_category_id'
            ]
        );

        $this->hasOne(
            'Forum_Category as Parent',
            [
            'local'   => 'parent_category_id',
            'foreign' => 'id'
            ]
        );

        $this->hasOne(
            'Forum_Category as Rootcategory',
            [
            'local'   => 'root_category_id',
            'foreign' => 'id'
            ]
        );
    }
}
