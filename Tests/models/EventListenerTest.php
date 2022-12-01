<?php
class EventListenerTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', 100);
        $this->hasColumn('password', 'string', 8);
    }
    public function setUp(): void
    {
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
