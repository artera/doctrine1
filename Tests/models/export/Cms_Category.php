<?php
class Cms_Category extends \Doctrine1\Record
{
    public function setUp(): void
    {
        $this->hasMany('Cms_CategoryLanguages as langs', ['local' => 'id', 'foreign' => 'category_id']);
    }

    public function setTableDefinition(): void
    {
        $this->hasColumn('created', 'timestamp');
        $this->hasColumn('parent', 'integer', 11);
        $this->hasColumn('position', 'integer', 3);
        $this->hasColumn('active', 'integer', 11);
        $this->getTable()->collate = 'utf8_unicode_ci';
        $this->getTable()->charset = 'utf8';
        $this->getTable()->type = 'INNODB';
        $this->index('index_parent', ['fields' => ['parent']]);
    }
}
