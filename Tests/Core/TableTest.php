<?php
namespace Tests\Core;

use Tests\DoctrineUnitTestCase;

class TableTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['FieldNameTest'];

    public function testInitializingNewTableWorksWithoutConnection()
    {
        $table = new \Doctrine_Table('Test', static::$conn);
        $this->assertEquals($table->getComponentName(), 'Test');
    }

    public function testFieldConversion()
    {
        static::$dbh->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_UPPER);

        $t = new \FieldNameTest();

        $t->someColumn = 'abc';
        $t->someEnum   = 'php';
        $t->someInt    = 1;
        $t->someArray  = [];
        $obj           = new \StdClass();
        $t->someObject = $obj;

        $this->assertEquals($t->someColumn, 'abc');
        $this->assertEquals($t->someEnum, 'php');
        $this->assertEquals($t->someInt, 1);
        $this->assertEquals($t->someArray, []);
        $this->assertEquals($t->someObject, $obj);

        $t->save();

        $this->assertEquals($t->someColumn, 'abc');
        $this->assertEquals($t->someEnum, 'php');
        $this->assertEquals($t->someInt, 1);
        $this->assertEquals($t->someArray, []);
        $this->assertEquals($t->someObject, $obj);

        $t->refresh();

        $this->assertEquals($t->someColumn, 'abc');
        $this->assertEquals($t->someEnum, 'php');
        $this->assertEquals($t->someInt, 1);
        $this->assertEquals($t->someArray, []);
        $this->assertEquals($t->someObject, $obj);

        static::$connection->clear();

        $t = static::$connection->getTable('FieldNameTest')->find(1);

        $this->assertEquals($t->someColumn, 'abc');
        $this->assertEquals($t->someEnum, 'php');
        $this->assertEquals($t->someInt, 1);
        $this->assertEquals($t->someArray, []);
        $this->assertEquals($t->someObject, $obj);

        static::$dbh->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
    }

    public function testGetForeignKey()
    {
        $fk = static::$objTable->getRelation('Group');
        $this->assertTrue($fk instanceof \Doctrine_Relation_Association);
        $this->assertTrue($fk->getTable() instanceof \Doctrine_Table);
        $this->assertTrue($fk->getType() == \Doctrine_Relation::MANY);
        $this->assertTrue($fk->getLocal() == 'user_id');
        $this->assertTrue($fk->getForeign() == 'group_id');

        $fk = static::$objTable->getRelation('Email');
        $this->assertTrue($fk instanceof \Doctrine_Relation_LocalKey);
        $this->assertTrue($fk->getTable() instanceof \Doctrine_Table);
        $this->assertTrue($fk->getType() == \Doctrine_Relation::ONE);
        $this->assertTrue($fk->getLocal() == 'email_id');
        $this->assertTrue($fk->getForeign() == $fk->getTable()->getIdentifier());


        $fk = static::$objTable->getRelation('Phonenumber');
        $this->assertTrue($fk instanceof \Doctrine_Relation_ForeignKey);
        $this->assertTrue($fk->getTable() instanceof \Doctrine_Table);
        $this->assertTrue($fk->getType() == \Doctrine_Relation::MANY);
        $this->assertTrue($fk->getLocal() == static::$objTable->getIdentifier());
        $this->assertTrue($fk->getForeign() == 'entity_id');
    }
    public function testGetComponentName()
    {
        $this->assertTrue(static::$objTable->getComponentName() == 'User');
    }

    public function testGetTableName()
    {
        $this->assertTrue(static::$objTable->tableName == 'entity');
    }

    public function testGetConnection()
    {
        $this->assertTrue(static::$objTable->getConnection() instanceof \Doctrine_Connection);
    }

    public function testGetData()
    {
        $this->assertTrue(static::$objTable->getData() == []);
    }

    public function testSetSequenceName()
    {
        static::$objTable->sequenceName = 'test-seq';
        $this->assertEquals(static::$objTable->sequenceName, 'test-seq');
        static::$objTable->sequenceName = null;
    }

    public function testCreate()
    {
        $record = static::$objTable->create();
        $this->assertTrue($record instanceof \Doctrine_Record);
        $this->assertTrue($record->state() == \Doctrine_Record::STATE_TCLEAN);
    }

    public function testFind()
    {
        $record = static::$objTable->find(4);
        $this->assertTrue($record instanceof \Doctrine_Record);

        $record = static::$objTable->find('4');
        $this->assertTrue($record instanceof \Doctrine_Record);

        $record = static::$objTable->find('4', \Doctrine_Core::HYDRATE_ARRAY);
        $this->assertTrue(is_array($record));
        $this->assertTrue(! is_object($record));
        $this->assertTrue(array_key_exists('id', $record));
        $this->assertTrue(array_key_exists('name', $record));
        $this->assertTrue(! $record instanceof \Doctrine_Record);

        $record = static::$objTable->find(123);
        $this->assertTrue($record === false);

        $record = static::$objTable->find(null);
        $this->assertTrue($record === false);

        $record = static::$objTable->find(false);
        $this->assertTrue($record === false);
    }

    public function testFindAll()
    {
        $users = static::$objTable->findAll();
        $this->assertEquals($users->count(), 8);
        $this->assertTrue($users instanceof \Doctrine_Collection);

        $users = static::$objTable->findAll(\Doctrine_Core::HYDRATE_ARRAY);
        $this->assertTrue(! $users instanceof \Doctrine_Collection);
        $this->assertTrue(is_array($users));
        $this->assertTrue(! is_object($users));
        $this->assertEquals(count($users), 8);
    }

    public function testFindByDql()
    {
        $users = static::$objTable->findByDql("name LIKE '%Arnold%'");
        $this->assertEquals($users->count(), 1);
        $this->assertTrue($users instanceof \Doctrine_Collection);
    }

    public function testFindByXXX()
    {
        $users = static::$objTable->findByName('zYne');
        $this->assertEquals($users->count(), 1);
        $this->assertTrue($users instanceof \Doctrine_Collection);
    }

    public function testGetProxy()
    {
        $user = static::$objTable->getProxy(4);
        $this->assertTrue($user instanceof \Doctrine_Record);
        $record = static::$objTable->find(123);
    }

    public function testGetColumns()
    {
        $columns = static::$objTable->getColumns();
        $this->assertTrue(is_array($columns));
    }

    public function testApplyInheritance()
    {
        $this->assertEquals(static::$objTable->applyInheritance('id = 3'), 'id = 3 AND type = ?');
    }
}
