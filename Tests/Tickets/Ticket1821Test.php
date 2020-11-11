<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1821Test extends DoctrineUnitTestCase
    {
        protected static array $tables = [
            'Ticket1821_Record',
            'Ticket1821_Record_ID_Aliased',
            'Ticket1821_Record_Column_Aliased',
            'Ticket1821_Record_Full_Aliased',
        ];

        public static function prepareData(): void
        {
        }

        public function execTest($klass)
        {
            //stores old validation setting
            $validation = \Doctrine_Manager::getInstance()->getAttribute(\Doctrine_Core::ATTR_VALIDATE);
            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, \Doctrine_Core::VALIDATE_ALL);

            $record       = new $klass();
            $record->name = 'test';
            $record->save();

            \Doctrine_Manager::getInstance()->setAttribute(\Doctrine_Core::ATTR_VALIDATE, $validation);
        }

        public function testShouldAllowNotUsingAliases()
        {
            $this->execTest('Ticket1821_Record');
        }

        public function testShouldAllowUsingAliasesOnId()
        {
            $this->execTest('Ticket1821_Record_ID_Aliased');
        }

        public function testShouldAllowUsingAliasesOnColumn()
        {
            $this->execTest('Ticket1821_Record_Column_Aliased');
        }

        public function testShouldAllowUsingAliasesOnBoth()
        {
            $this->execTest('Ticket1821_Record_Full_Aliased');
        }
    }
}

namespace {
    class Ticket1821_Record_Full_Aliased extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn(
                'user_id as id',
                'integer',
                4,
                [
                'autoincrement' => true,
                'notnull'       => true,
                'primary'       => true
                ]
            );
            $this->hasColumn(
                'user_name as name',
                'string',
                255,
                [
                'notnull' => true,
                'unique'  => true
                ]
            );
        }
    }

    class Ticket1821_Record_ID_Aliased extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn(
                'user_id as id',
                'integer',
                4,
                [
                'autoincrement' => true,
                'notnull'       => true,
                'primary'       => true
                ]
            );
            $this->hasColumn(
                'name',
                'string',
                255,
                [
                'notnull' => true,
                'unique'  => true
                ]
            );
        }
    }

    class Ticket1821_Record_Column_Aliased extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn(
                'id',
                'integer',
                4,
                [
                'autoincrement' => true,
                'notnull'       => true,
                'primary'       => true
                ]
            );
            $this->hasColumn(
                'user_name as name',
                'string',
                255,
                [
                'notnull' => true,
                'unique'  => true
                ]
            );
        }
    }

    class Ticket1821_Record extends Doctrine_Record
    {
        public function setTableDefinition()
        {
            $this->hasColumn(
                'id',
                'integer',
                4,
                [
                'autoincrement' => true,
                'notnull'       => true,
                'primary'       => true
                ]
            );
            $this->hasColumn(
                'name',
                'string',
                255,
                [
                'notnull' => true,
                'unique'  => true
                ]
            );
        }
    }
}
