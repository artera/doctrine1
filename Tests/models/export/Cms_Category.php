<?php
class Cms_Category extends Doctrine_Record
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
        $this->option('collate', 'utf8_unicode_ci');
        $this->option('charset', 'utf8');
        $this->option('type', 'INNODB');
        $this->index('index_parent', ['fields' => ['parent']]);
    }
}
