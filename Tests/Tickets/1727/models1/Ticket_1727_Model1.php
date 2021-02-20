<?php
class Ticket_1727_Model1 extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 255);
    }
}
