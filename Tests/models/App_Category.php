<?php
class App_Category extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 32);
        $this->hasColumn('parent_id', 'integer');
    }
    public function setUp(): void
    {
        $this->hasMany(
            'App',
            [
            'local'   => 'id',
            'foreign' => 'app_category_id'
            ]
        );

        $this->hasMany(
            'App_Category as Parent',
            [
            'local'   => 'parent_id',
            'foreign' => 'id'
            ]
        );
    }
}
