<?php

class Ticket_2375_Model5 extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 255);
    }
}
