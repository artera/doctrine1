<?php
class Location extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('lat', 'double', 10, []);
        $this->hasColumn('lon', 'double', 10, []);
    }
}
