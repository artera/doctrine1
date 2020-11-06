<?php
class Location extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('lat', 'double', 10, array());
        $this->hasColumn('lon', 'double', 10, array());
    }
}
