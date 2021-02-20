<?php
class RecordHookTest extends Doctrine_Record
{
    protected $_messages = [];

    public function setTableDefinition(): void
    {
        $this->hasColumn('name', 'string', null, ['primary' => true]);
    }
    public function preSave($event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postSave($event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function preInsert($event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postInsert($event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function preUpdate($event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postUpdate($event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function preDelete($event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function postDelete($event)
    {
        $this->_messages[] = __FUNCTION__;
    }
    public function pop()
    {
        return array_pop($this->_messages);
    }
}
