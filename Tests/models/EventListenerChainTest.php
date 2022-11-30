<?php
class EventListenerChainTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 100);
    }
    public function setUp(): void
    {
        $chain = new \Doctrine1\EventListener\Chain();
        $chain->add(new EventListener_TestA());
        $chain->add(new EventListener_TestB());
    }
}

class EventListener_TestA extends \Doctrine1\EventListener
{
}
class EventListener_TestB extends \Doctrine1\EventListener
{
}
