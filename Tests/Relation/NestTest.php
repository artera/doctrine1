<?php
namespace Tests\Relation;

use Tests\DoctrineUnitTestCase;

class NestTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }

    protected static array $tables = ['NestTest', 'NestReference', 'Entity', 'EntityReference'];

    public function testInitJoinTableSelfReferencingInsertingData()
    {
        $e       = new \Entity();
        $e->name = 'Entity test';

        $this->assertTrue($e->Entity[0] instanceof \Entity);
        $this->assertTrue($e->Entity[1] instanceof \Entity);

        $this->assertEquals($e->Entity[0]->state(), \Doctrine_Record_State::TCLEAN());
        $this->assertEquals($e->Entity[1]->state(), \Doctrine_Record_State::TCLEAN());

        $e->Entity[0]->name = 'Friend 1';
        $e->Entity[1]->name = 'Friend 2';

        $e->Entity[0]->Entity[0]->name = 'Friend 1 1';
        $e->Entity[0]->Entity[1]->name = 'Friend 1 2';

        $e->Entity[1]->Entity[0]->name = 'Friend 2 1';
        $e->Entity[1]->Entity[1]->name = 'Friend 2 2';

        $this->assertEquals($e->Entity[0]->name, 'Friend 1');
        $this->assertEquals($e->Entity[1]->name, 'Friend 2');

        $this->assertEquals($e->Entity[0]->Entity[0]->name, 'Friend 1 1');
        $this->assertEquals($e->Entity[0]->Entity[1]->name, 'Friend 1 2');

        $this->assertEquals($e->Entity[1]->Entity[0]->name, 'Friend 2 1');
        $this->assertEquals($e->Entity[1]->Entity[1]->name, 'Friend 2 2');

        $this->assertEquals($e->Entity[0]->state(), \Doctrine_Record_State::TDIRTY());
        $this->assertEquals($e->Entity[1]->state(), \Doctrine_Record_State::TDIRTY());

        $count = count(static::$conn);

        $e->save();

        $this->assertEquals(($count + 13), static::$conn->count());
    }

    public function testNestRelationsFetchingData()
    {
        static::$connection->clear();

        $e = static::$conn->queryOne('FROM Entity e LEFT JOIN e.Entity e2 LEFT JOIN e2.Entity e3 WHERE (e.id = 1) ORDER BY e.name, e2.name, e3.name');
        $this->assertEquals($e->state(), \Doctrine_Record_State::CLEAN());

        $this->assertTrue($e->Entity[0] instanceof \Entity);
        $this->assertTrue($e->Entity[1] instanceof \Entity);

        $this->assertEquals($e->Entity[0]->name, 'Friend 1');
        $this->assertEquals($e->Entity[1]->name, 'Friend 2');

        $this->assertEquals($e->Entity[0]->Entity[0]->name, 'Entity test');
        $this->assertEquals($e->Entity[0]->Entity[1]->name, 'Friend 1 1');
        $this->assertEquals($e->Entity[0]->Entity[2]->name, 'Friend 1 2');

        $this->assertEquals($e->Entity[0]->Entity[0]->name, 'Entity test');
        $this->assertEquals($e->Entity[1]->Entity[1]->name, 'Friend 2 1');
        $this->assertEquals($e->Entity[1]->Entity[2]->name, 'Friend 2 2');

        $this->assertEquals($e->Entity[0]->state(), \Doctrine_Record_State::CLEAN());
        $this->assertEquals($e->Entity[1]->state(), \Doctrine_Record_State::CLEAN());

        $this->assertTrue(is_numeric($e->id));

        $result = static::$conn->execute('SELECT * FROM entity_reference')->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertEquals(count($result), 6);

        //$stmt = static::$dbh->prepare($q);

        //$stmt->execute(array(18));
        //$result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        //print_r($result);

        static::$connection->clear();

        $e = $e->getTable()->find($e->id);

        $count = count(static::$conn);

        $this->assertTrue($e instanceof \Entity);

        $this->assertTrue($e->Entity[0] instanceof \Entity);
        $this->assertTrue($e->Entity[1] instanceof \Entity);

        $this->assertEquals(count(static::$conn), ($count + 1));

        $this->assertEquals($e->Entity[0]->name, 'Friend 1');
        $this->assertEquals($e->Entity[1]->name, 'Friend 2');

        $this->assertEquals($e->Entity[0]->Entity[0]->name, 'Entity test');
        $this->assertEquals($e->Entity[0]->Entity[1]->name, 'Friend 1 1');

        $this->assertEquals(count(static::$conn), ($count + 2));

        $this->assertEquals($e->Entity[1]->Entity[0]->name, 'Entity test');
        $this->assertEquals($e->Entity[1]->Entity[1]->name, 'Friend 2 1');

        $this->assertEquals(count(static::$conn), ($count + 3));

        $this->assertEquals($e->Entity[0]->state(), \Doctrine_Record_State::CLEAN());
        $this->assertEquals($e->Entity[1]->state(), \Doctrine_Record_State::CLEAN());

        $coll = static::$connection->query("FROM Entity WHERE Entity.name = 'Friend 1'");
        $this->assertEquals($coll->count(), 1);
        $this->assertEquals($coll[0]->state(), \Doctrine_Record_State::CLEAN());

        $this->assertEquals($coll[0]->name, 'Friend 1');

        $query = new \Doctrine_Query(static::$connection);

        $query->from('Entity e LEFT JOIN e.Entity e2')->where("e2.name = 'Friend 1 1'");

        $coll = $query->execute();

        $this->assertEquals($coll->count(), 1);
    }

    public function testNestRelationsParsing()
    {
        $nest = new \NestTest();

        $rel = $nest->getTable()->getRelation('Parents');

        $this->assertTrue($rel instanceof \Doctrine_Relation_Nest);

        $this->assertEquals($rel->getLocal(), 'child_id');
        $this->assertEquals($rel->getForeign(), 'parent_id');
    }

    public function testNestRelationsSaving()
    {
        $nest                                 = new \NestTest();
        $nest->name                           = 'n 1';
        $nest->Parents[0]->name               = 'p 1';
        $nest->Parents[1]->name               = 'p 2';
        $nest->Parents[2]->name               = 'p 3';
        $nest->Children[0]->name              = 'c 1';
        $nest->Children[0]->Children[0]->name = 'c c 1';
        $nest->Children[0]->Children[1]->name = 'c c 2';
        $nest->Children[1]->name              = 'c 2';
        $nest->Children[1]->Parents[]->name   = 'n 2';
        $nest->save();

        static::$connection->clear();
    }

    public function testNestRelationsLoading()
    {
        $nest = static::$conn->queryOne('FROM NestTest n WHERE n.id = 1');

        $this->assertEquals($nest->Parents->count(), 3);
        $this->assertEquals($nest->Children->count(), 2);
        $this->assertEquals($nest->Children[0]->Children->count(), 2);
        $this->assertEquals($nest->Children[1]->Parents->count(), 2);

        static::$connection->clear();
    }

    public function testEqualNestRelationsLoading()
    {
        $nest = static::$conn->queryOne('FROM NestTest n WHERE n.id = 1');

        $this->assertEquals($nest->Relatives->count(), 5);
    }

    public function testNestRelationsQuerying()
    {
        static::$connection->clear();

        $q = new \Doctrine_Query();

        $q->from('NestTest n')->innerJoin('n.Parents p')->where('n.id = 1');

        $n = $q->execute();

        $this->assertEquals($n[0]->Parents->count(), 3);
    }

    public function testNestRelationsQuerying2()
    {
        static::$connection->clear();

        $q = new \Doctrine_Query();

        $q->from('NestTest n')->innerJoin('n.Children c')->where('n.id = 1');

        $n = $q->execute();

        $this->assertEquals($n[0]->Children->count(), 2);
    }

    public function testEqualNestRelationsQuerying()
    {
        static::$connection->clear();

        $q = new \Doctrine_Query();

        $q->from('NestTest n')->innerJoin('n.Relatives r')->where('n.id = 1');

        $n = $q->execute();

        $this->assertEquals($q->getSqlQuery(), 'SELECT n.id AS n__id, n.name AS n__name, n2.id AS n2__id, n2.name AS n2__name FROM nest_test n INNER JOIN nest_reference n3 ON (n.id = n3.child_id OR n.id = n3.parent_id) INNER JOIN nest_test n2 ON (n2.id = n3.parent_id OR n2.id = n3.child_id) AND n2.id != n.id WHERE (n.id = 1)');

        $this->assertEquals($n[0]->Relatives->count(), 5);
    }
}
