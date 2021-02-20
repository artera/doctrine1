<?php
class Location extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('lat', 'double', 10, []);
        $this->hasColumn('lon', 'double', 10, []);
    }
}
