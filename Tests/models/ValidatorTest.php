<?php
class ValidatorTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('mymixed', 'string', 100);
        $this->hasColumn('mystring', 'string', 100, ['notnull', 'unique']);
        $this->hasColumn('myarray', 'array', 1000);
        $this->hasColumn('myobject', 'object', 1000);
        $this->hasColumn('myinteger', 'integer', 11);
        $this->hasColumn('myrange', 'integer', 11, ['range' => [
            'min' => 4,
            'max' => 123,
        ]]);
        $this->hasColumn('myregexp', 'string', 5, ['regexp' => ['pattern' => '/^[0-9]+$/'],
        ]);

        $this->hasColumn('myemail', 'string', 100, ['email']);
        $this->hasColumn('myemail2', 'string', 100, ['email', 'notblank']);
    }
}
