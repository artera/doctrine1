<?php
class FieldNameTest extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('someColumn', 'string', 200, ['default' => 'some string']);
        $this->hasColumn('someEnum', 'enum', 4, ['default' => 'php', 'values' => ['php', 'java', 'python']]);
        $this->hasColumn('someArray', 'array', 100, ['default' => []]);
        $this->hasColumn('someObject', 'object', 200, ['default' => new \stdClass]);
        $this->hasColumn('someInt', 'integer', 20, ['default' => 11]);
    }
}
