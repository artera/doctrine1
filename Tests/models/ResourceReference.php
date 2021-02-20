<?php
class ResourceReference extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('type_id', 'integer');
        $this->hasColumn('resource_id', 'integer');
    }
}
