<?php
class PolicyCodeN extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->setTableName('policy_codes');
        $this->hasColumn('id', 'integer', 4, ['notnull' => true, 'primary' => true, 'autoincrement' => true]);
        $this->hasColumn('code', 'integer', 4, [  'notnull' => true,  'notblank' => true,]);
        $this->hasColumn('description', 'string', 4000, [  'notnull' => true,  'notblank' => true,]);
    }
}
