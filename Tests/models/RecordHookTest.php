<?php

use Doctrine1\Event;

class RecordHookTest extends \Doctrine1\Record
{
    protected $messages = [];

    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', null, ['primary' => true]);
    }
    public function preSave(Event $event): void
    {
        $this->messages[] = __FUNCTION__;
    }
    public function postSave(Event $event): void
    {
        $this->messages[] = __FUNCTION__;
    }
    public function preInsert(Event $event): void
    {
        $this->messages[] = __FUNCTION__;
    }
    public function postInsert(Event $event): void
    {
        $this->messages[] = __FUNCTION__;
    }
    public function preUpdate(Event $event): void
    {
        $this->messages[] = __FUNCTION__;
    }
    public function postUpdate(Event $event): void
    {
        $this->messages[] = __FUNCTION__;
    }
    public function preDelete(Event $event): void
    {
        $this->messages[] = __FUNCTION__;
    }
    public function postDelete(Event $event): void
    {
        $this->messages[] = __FUNCTION__;
    }
    public function pop()
    {
        return array_pop($this->messages);
    }
}
