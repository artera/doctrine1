<?php
namespace Tests\Relation;

use Tests\DoctrineUnitTestCase;

class OneToManyTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    protected static array $tables = ['Entity', 'Phonenumber', 'Email', 'Policy', 'PolicyAsset', 'Role', 'Auth'];
    public function testRelationParsing()
    {
        $table = static::$conn->getTable('Entity');

        $rel = $table->getRelation('Phonenumber');

        $this->assertTrue($rel instanceof \Doctrine_Relation_ForeignKey);

        $rel = $table->getRelation('Email');

        $this->assertTrue($rel instanceof \Doctrine_Relation_LocalKey);
    }

    public function testRelationParsing2()
    {
        $table = static::$conn->getTable('Phonenumber');

        $rel = $table->getRelation('Entity');

        $this->assertTrue($rel instanceof \Doctrine_Relation_LocalKey);
    }

    public function testRelationParsing3()
    {
        $table = static::$conn->getTable('Policy');

        $rel = $table->getRelation('PolicyAssets');

        $this->assertTrue($rel instanceof \Doctrine_Relation_ForeignKey);
    }
    public function testRelationSaving()
    {
        $p                = new \Policy();
        $p->policy_number = '123';

        $a        = new \PolicyAsset();
        $a->value = '123.13';

        $p->PolicyAssets[] = $a;
        $p->save();

        $this->assertEquals($a->policy_number, '123');
    }
    public function testRelationSaving2()
    {
        $e       = new \Entity();
        $e->name = 'test';
        $e->save();

        $nr              = new \Phonenumber();
        $nr->phonenumber = '1234556';
        $nr->save();
        $nr->Entity = $e;
    }
    public function testRelationSaving3()
    {
        // create roles and user with role1 and role2
        static::$conn->beginTransaction();
        $role       = new \Role();
        $role->name = 'role1';
        $role->save();

        $auth       = new \Auth();
        $auth->name = 'auth1';
        $auth->Role = $role;
        $auth->save();

        static::$conn->commit();

        static::$conn->clear();

        $auths = static::$conn->query('FROM Auth a LEFT JOIN a.Role r');

        $this->assertEquals($auths[0]->Role->name, 'role1');
    }
}
