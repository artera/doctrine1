<?php
namespace Tests\Query;

use Tests\DoctrineUnitTestCase;

class UpdateTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    protected static array $tables = ['Entity', 'User', 'EnumTest'];
    public function testUpdateAllWithColumnAggregationInheritance()
    {
        $q = new \Doctrine1\Query();

        $q->parseDqlQuery("UPDATE User u SET u.name = 'someone'");

        $this->assertEquals($q->getSqlQuery(), "UPDATE entity SET name = 'someone' WHERE (type = 0)");

        $q = new \Doctrine1\Query();

        $q->update('User u')->set('u.name', "'someone'");

        $this->assertEquals($q->getSqlQuery(), "UPDATE entity SET name = 'someone' WHERE (type = 0)");
    }

    public function testUpdateWorksWithMultipleColumns()
    {
        $q = new \Doctrine1\Query();

        $q->parseDqlQuery("UPDATE User u SET u.name = 'someone', u.email_id = 5");

        $this->assertEquals($q->getSqlQuery(), "UPDATE entity SET name = 'someone', email_id = 5 WHERE (type = 0)");

        $q = new \Doctrine1\Query();

        $q->update('User u')->set('u.name', "'someone'")->set('u.email_id', 5);

        $this->assertEquals($q->getSqlQuery(), "UPDATE entity SET name = 'someone', email_id = 5 WHERE (type = 0)");
    }

    public function testUpdateSupportsConditions()
    {
        $q = new \Doctrine1\Query();

        $q->parseDqlQuery("UPDATE User u SET u.name = 'someone' WHERE u.id = 5");

        $this->assertEquals($q->getSqlQuery(), "UPDATE entity SET name = 'someone' WHERE (id = 5 AND (type = 0))");
    }
    public function testUpdateSupportsColumnReferencing()
    {
        $q = new \Doctrine1\Query();

        $q->update('User u')->set('u.id', 'u.id + 1');

        $this->assertEquals($q->getSqlQuery(), 'UPDATE entity SET id = id + 1 WHERE (type = 0)');
    }
    public function testUpdateSupportsComplexExpressions()
    {
        $q = new \Doctrine1\Query();
        $q->update('User u')->set('u.name', "CONCAT(?, CONCAT(':', SUBSTRING(u.name, LOCATE(':', u.name)+1, LENGTH(u.name) - LOCATE(':', u.name)+1)))", ['gblanco'])
            ->where('u.id IN (SELECT u2.id FROM User u2 WHERE u2.name = ?) AND u.email_id = ?', ['guilhermeblanco', 5]);
        $this->assertEquals($q->getSqlQuery(), "UPDATE entity SET name = CONCAT(?, CONCAT(':', SUBSTRING(name, LOCATE(':', name)+1, LENGTH(name) - LOCATE(':', name)+1))) WHERE (id IN (SELECT e2.id AS e2__id FROM entity e2 WHERE (e2.name = ? AND (e2.type = 0))) AND email_id = ?) AND (type = 0)");
    }
    public function testUpdateSupportsNullSetting()
    {
        $user            = new \User();
        $user->name      = 'jon';
        $user->loginname = 'jwage';
        $user->password  = 'changeme';
        $user->save();

        $id = $user->id;
        $user->free();

        $q = \Doctrine1\Query::create()
            ->update('User u')
            ->set('u.name', 'NULL')
            ->where('u.id = ?', $id);

        $this->assertEquals($q->getSqlQuery(), 'UPDATE entity SET name = NULL WHERE (id = ? AND (type = 0))');

        $q->execute();

        $user = \Doctrine1\Query::create()
            ->from('User u')
            ->where('u.id = ?', $id)
            ->fetchOne();

        $this->assertEquals($user->name, '');
    }
    public function testEnumAndAnotherColumnUpdate()
    {
        $enumTest         = new \EnumTest();
        $enumTest->status = 'open';
        $enumTest->text   = 'test';
        $enumTest->save();

        $id = $enumTest->id;
        $enumTest->free();

        $q = \Doctrine1\Query::create()
            ->update('EnumTest t')
            ->set('status', '?', 'closed')
            ->set('text', '?', 'test2')
            ->where('t.id = ?', $id);
        $q->execute();

        $this->assertEquals($q->getSqlQuery(), 'UPDATE enum_test SET status = ?, text = ? WHERE (id = ?)');

        $enumTest = \Doctrine1\Query::create()
            ->from('EnumTest t')
            ->where('t.id = ?', $id)
            ->fetchOne();

        $this->assertEquals($enumTest->status, 'closed');
        $this->assertEquals($enumTest->text, 'test2');
    }
}
