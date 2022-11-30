<?php
class ResourceReference extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('type_id', 'integer');
        $this->hasColumn('resource_id', 'integer');
    }
}
