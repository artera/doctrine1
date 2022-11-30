<?php
class QueryTest_UserRank extends \Doctrine1\Record
{
    public function setTableDefinition(): void
    {
        $this->hasColumn('rankId', 'integer', 4, ['primary' => true]);
        $this->hasColumn('userId', 'integer', 4, ['primary' => true]);
    }
}
