<?php
class Ticket_2375_Model3 extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 255);
    }
}
