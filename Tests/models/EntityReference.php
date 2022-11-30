<?php
class EntityReference extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('entity1', 'integer', null, 'primary');
        $this->hasColumn('entity2', 'integer', null, 'primary');
        //$this->setPrimaryKey(array('entity1', 'entity2'));
    }
}
