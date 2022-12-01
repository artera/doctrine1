<?php

class Cms_CategoryLanguages extends \Doctrine1\Record
{
    public function setUp(): void
    {
        $this->setCollectionKey('language_id');
        $this->hasOne('Cms_Category as category', ['local' => 'category_id', 'foreign' => 'id', 'onDelete' => 'CASCADE']);
    }

    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 256);
        $this->hasColumn('category_id', 'integer', 11);
        $this->hasColumn('language_id', 'integer', 11);
        $this->getTable()->collate = 'utf8_unicode_ci';
        $this->getTable()->charset = 'utf8';
        $this->getTable()->type = 'InnoDB';
        $this->index('index_category', ['fields' => ['category_id']]);
        $this->index('index_language', ['fields' => ['language_id']]);
    }
}
