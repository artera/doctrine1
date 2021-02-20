<?php
class EventListenerTest extends Doctrine_Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('password', 'string', 8);
    }
    public function setUp(): void
    {
        //$this->attribute(\Doctrine_Core::ATTR_LISTENER, new \Doctrine_EventListener_AccessorInvoker());
    }
    public function getName($name)
    {
        return strtoupper($name);
    }
    public function setPassword($password)
    {
        return md5($password);
    }
}
