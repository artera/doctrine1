<?php
class RecordHookTest extends Doctrine_Record
{
    protected $messages = [];

    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', null, ['primary' => true]);
    }
    public function preSave($event)
    {
        $this->messages[] = __FUNCTION__;
    }
    public function postSave($event)
    {
        $this->messages[] = __FUNCTION__;
    }
    public function preInsert($event)
    {
        $this->messages[] = __FUNCTION__;
    }
    public function postInsert($event)
    {
        $this->messages[] = __FUNCTION__;
    }
    public function preUpdate($event)
    {
        $this->messages[] = __FUNCTION__;
    }
    public function postUpdate($event)
    {
        $this->messages[] = __FUNCTION__;
    }
    public function preDelete($event)
    {
        $this->messages[] = __FUNCTION__;
    }
    public function postDelete($event)
    {
        $this->messages[] = __FUNCTION__;
    }
    public function pop()
    {
        return array_pop($this->messages);
    }
}
