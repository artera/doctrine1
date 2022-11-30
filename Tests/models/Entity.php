<?php
/**
 * @phpstan-template T of \Doctrine1\Table
 * @phpstan-extends \Doctrine1\Record<T>
 */
class Entity extends \Doctrine1\Record
{
    public function setUp(): void
    {
        $this->hasOne('Email', ['local' => 'email_id', 'onDelete' => 'CASCADE']);
        $this->hasMany('Phonenumber', ['local' => 'id', 'foreign' => 'entity_id']);
        $this->hasOne('Account', ['foreign' => 'entity_id', 'onDelete' => 'CASCADE']);
        $this->hasMany(
            'Entity',
            ['local' => 'entity1',
            'refClass'                         => 'EntityReference',
            'foreign'                          => 'entity2',
            'equal'                            => true]
        );
    }
    public function setTableDefinition(): void
    {
        $this->hasColumn('id', 'integer', 20, ['autoincrement', 'primary']);
        $this->hasColumn('name', 'string', 50);
        $this->hasColumn('loginname', 'string', 20, ['unique']);
        $this->hasColumn('password', 'string', 16);
        $this->hasColumn('type', 'integer', 1);
        $this->hasColumn('created', 'integer', 11);
        $this->hasColumn('updated', 'integer', 11);
        $this->hasColumn('email_id', 'integer');
        $this->setSubclasses(['User' => ['type' => 0], 'Group' => ['type' => 1]]);
    }
}
