<?php
namespace Tests\Tickets {
    use Tests\DoctrineUnitTestCase;

    class Ticket1124Test extends DoctrineUnitTestCase
    {
        const NO_ALIAS         = 5;
        const SOMETHING_ELSE   = 8;
        const TABLEIZED_ALIAS  = 27;
        const CLASSIFIED_ALIAS = 29;
        const ANOTHER_ALIAS    = 30;

        public static function prepareTables(): void
        {
            static::$tables   = [];
            static::$tables[] = 'Ticket_1124_Record';

            parent::prepareTables();
        }

        public static function prepareData(): void
        {
            $record                  = new \Ticket_1124_Record();
            $record->no_alias        = self::NO_ALIAS;
            $record->somethingElse   = self::SOMETHING_ELSE;
            $record->tableizedAlias  = self::TABLEIZED_ALIAS;
            $record->ClassifiedAlias = self::CLASSIFIED_ALIAS;
            $record->another_Alias   = self::ANOTHER_ALIAS;
            $record->save();
        }

        private function assertIsSampleRecord($record)
        {
            $this->assertNotNull($record);
            $this->assertEquals($record->no_alias, self::NO_ALIAS);
            $this->assertEquals($record->somethingElse, self::SOMETHING_ELSE);
            $this->assertEquals($record->tableizedAlias, self::TABLEIZED_ALIAS);
            $this->assertEquals($record->ClassifiedAlias, self::CLASSIFIED_ALIAS);
        }

        public function testFindByUnaliasedColumnWorks()
        {
            $r = \Doctrine_Core::getTable('Ticket_1124_Record')->findOneByNoAlias(self::NO_ALIAS);
            $this->assertIsSampleRecord($r);
        }

        public function testFindByDisjointlyAliasedColumnWorks()
        {
            $r = \Doctrine_Core::getTable('Ticket_1124_Record')->findOneBysomethingElse(self::SOMETHING_ELSE);    // test currently fails
            $this->assertIsSampleRecord($r);
        }

        public function testFindByDisjointlyAliasedColumnWorks2()
        {
            $r = \Doctrine_Core::getTable('Ticket_1124_Record')->findOneBydisjoint_alias(self::SOMETHING_ELSE);    // test currently fails
            $this->assertIsSampleRecord($r);
        }

        public function testFindByDisjointlyAliasedColumnWorks3()
        {
            $r = \Doctrine_Core::getTable('Ticket_1124_Record')->findOneByDisjointAlias(self::SOMETHING_ELSE);    // test currently fails
            $this->assertIsSampleRecord($r);
        }

        public function testFindByTableizedAliasedColumnWorks()
        {
            $r = \Doctrine_Core::getTable('Ticket_1124_Record')->findOneBytableizedAlias(self::TABLEIZED_ALIAS);    // test currently fails
            $this->assertIsSampleRecord($r);
        }

        public function testFindByTableizedAliasedColumnWorks2()
        {
            $r = \Doctrine_Core::getTable('Ticket_1124_Record')->findOneBytableized_alias(self::TABLEIZED_ALIAS);    // test currently fails
            $this->assertIsSampleRecord($r);
        }

        public function testFindByClassifiedAliasedColumnWorks()
        {
            $r = \Doctrine_Core::getTable('Ticket_1124_Record')->findOneByClassifiedAlias(self::CLASSIFIED_ALIAS);    // test currently fails
            $this->assertIsSampleRecord($r);
        }

        public function testFindByAnotherAliasedColumnWorks()
        {
            $r = \Doctrine_Core::getTable('Ticket_1124_Record')->findOneByTest(self::ANOTHER_ALIAS);    // test currently fails
            $this->assertIsSampleRecord($r);
        }

        public function testFindByAnotherAliasedColumnWorks2()
        {
            $r = \Doctrine_Core::getTable('Ticket_1124_Record')->findOneBytest(self::ANOTHER_ALIAS);    // test currently fails
            $this->assertIsSampleRecord($r);
        }

        public function testFindByAnotherAliasedColumnWorks3()
        {
            $r = \Doctrine_Core::getTable('Ticket_1124_Record')->findOneByanother_Alias(self::ANOTHER_ALIAS);    // test currently fails
            $this->assertIsSampleRecord($r);
        }
    }
}

namespace {
    class Ticket_1124_Record extends Doctrine_Record
    {
        public function setTableDefinition(): void
        {
            $this->setTableName('emb1_record');
            $this->hasColumn('id', 'integer', 15, ['autoincrement' => true, 'unsigned' => true, 'primary' => true, 'notnull' => true]);
            $this->hasColumn('no_alias', 'integer', 4);    // column with no aliasing
            $this->hasColumn('disjoint_alias as somethingElse', 'integer', 4);    // column whose alias has no relation to the column itself
            $this->hasColumn('tableized_alias as tableizedAlias', 'integer', 4);    // column whose alias' tableized form is equivalent to the column name itself
            $this->hasColumn('w00t as ClassifiedAlias', 'integer', 4);
            $this->hasColumn('test as another_Alias', 'integer', 4);
        }
    }
}
