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
        $fk = static::$connection->getTable('User')->getRelation('Group');
        $this->assertTrue($fk instanceof \Doctrine_Relation_Association);
        $this->assertTrue($fk->getTable() instanceof \Doctrine_Table);
        $this->assertTrue($fk->getType() == \Doctrine_Relation::MANY);
        $this->assertTrue($fk->getLocal() == 'user_id');
        $this->assertTrue($fk->getForeign() == 'group_id');

        $fk = static::$connection->getTable('User')->getRelation('Email');
        $this->assertTrue($fk instanceof \Doctrine_Relation_LocalKey);
        $this->assertTrue($fk->getTable() instanceof \Doctrine_Table);
        $this->assertTrue($fk->getType() == \Doctrine_Relation::ONE);
        $this->assertTrue($fk->getLocal() == 'email_id');
        $this->assertTrue($fk->getForeign() == $fk->getTable()->getIdentifier());


        $fk = static::$connection->getTable('User')->getRelation('Phonenumber');
        $this->assertTrue($fk instanceof \Doctrine_Relation_ForeignKey);
        $this->assertTrue($fk->getTable() instanceof \Doctrine_Table);
        $this->assertTrue($fk->getType() == \Doctrine_Relation::MANY);
        $this->assertTrue($fk->getLocal() == static::$connection->getTable('User')->getIdentifier());
        $this->assertTrue($fk->getForeign() == 'entity_id');
    }
    public function testGetComponentName()
    {
        $this->assertTrue(static::$connection->getTable('User')->getComponentName() == 'User');
    }

    public function testGetTableName()
    {
        $this->assertTrue(static::$connection->getTable('User')->tableName == 'entity');
    }

    public function testGetConnection()
    {
        $this->assertTrue(static::$connection->getTable('User')->getConnection() instanceof \Doctrine_Connection);
    }

    public function testGetData()
    {
        $this->assertTrue(static::$connection->getTable('User')->getData() == []);
    }

    public function testSetSequenceName()
    {
        static::$connection->getTable('User')->sequenceName = 'test-seq';
        $this->assertEquals(static::$connection->getTable('User')->sequenceName, 'test-seq');
        static::$connection->getTable('User')->sequenceName = null;
    }

    public function testCreate()
    {
        $record = static::$connection->getTable('User')->create();
        $this->assertTrue($record instanceof \Doctrine_Record);
        $this->assertTrue($record->state() == \Doctrine_Record_State::TCLEAN());
    }

    public function testFind()
    {
        $record = static::$connection->getTable('User')->find(4);
        $this->assertTrue($record instanceof \Doctrine_Record);

        $record = static::$connection->getTable('User')->find('4');
        $this->assertTrue($record instanceof \Doctrine_Record);

        $record = static::$connection->getTable('User')->find('4', \Doctrine_Core::HYDRATE_ARRAY);
        $this->assertTrue(is_array($record));
        $this->assertTrue(!is_object($record));
        $this->assertTrue(array_key_exists('id', $record));
        $this->assertTrue(array_key_exists('name', $record));
        $this->assertTrue(!$record instanceof \Doctrine_Record);

        $record = static::$connection->getTable('User')->find(123);
        $this->assertFalse($record);

        $record = static::$connection->getTable('User')->find(null);
        $this->assertFalse($record);

        $record = static::$connection->getTable('User')->find(false);
        $this->assertFalse($record);
    }

    public function testFindAll()
    {
        $users = static::$connection->getTable('User')->findAll();
        $this->assertEquals($users->count(), 8);
        $this->assertTrue($users instanceof \Doctrine_Collection);

        $users = static::$connection->getTable('User')->findAll(\Doctrine_Core::HYDRATE_ARRAY);
        $this->assertTrue(!$users instanceof \Doctrine_Collection);
        $this->assertTrue(is_array($users));
        $this->assertTrue(!is_object($users));
        $this->assertEquals(count($users), 8);
    }

    public function testFindByDql()
    {
        $users = static::$connection->getTable('User')->findByDql("name LIKE '%Arnold%'");
        $this->assertEquals($users->count(), 1);
        $this->assertTrue($users instanceof \Doctrine_Collection);
    }

    public function testFindByXXX()
    {
        $users = static::$connection->getTable('User')->findByName('zYne');
        $this->assertEquals($users->count(), 1);
        $this->assertTrue($users instanceof \Doctrine_Collection);
    }

    public function testGetProxy()
    {
        $user = static::$connection->getTable('User')->getProxy(4);
        $this->assertTrue($user instanceof \Doctrine_Record);
        $record = static::$connection->getTable('User')->find(123);
    }

    public function testGetColumns()
    {
        $columns = static::$connection->getTable('User')->getColumns();
        $this->assertTrue(is_array($columns));
    }

    public function testApplyInheritance()
    {
        $this->assertEquals(static::$connection->getTable('User')->applyInheritance('id = 3'), 'id = 3 AND type = ?');
    }
}
