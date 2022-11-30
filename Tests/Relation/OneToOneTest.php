<?php
namespace Tests\Relation;

use Tests\DoctrineUnitTestCase;

class OneToOneTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    protected static array $tables = ['gnatUser','gnatEmail','Email','Entity','Record_City', 'Record_Country', 'SelfRefTest'];

    public function testSelfReferentialOneToOneRelationsAreSupported()
    {
        $ref = new \SelfRefTest();

        $rel = $ref->getTable()->getRelation('createdBy');

        $this->assertEquals($rel->getForeign(), 'id');
        $this->assertEquals($rel->getLocal(), 'created_by');

        $ref->name            = 'ref 1';
        $ref->createdBy = new \SelfRefTest();
        $ref->createdBy->name = 'ref 2';

        $ref->save();
    }
    public function testSelfReferentialOneToOneRelationsAreSupported2()
    {
        static::$connection->clear();

        $ref = static::$conn->queryOne("FROM SelfRefTest s WHERE s.name = 'ref 1'");
        $this->assertEquals($ref->name, 'ref 1');
        $this->assertEquals($ref->createdBy->name, 'ref 2');
    }

    public function testUnsetRelation()
    {
        $user           = new \User();
        $user->name     = 'test';
        $email          = new \Email();
        $email->address = 'test@test.com';
        $user->Email    = $email;
        $user->save();
        $this->assertTrue($user->Email instanceof \Email);
        $user->Email = \Doctrine1\None::instance();
        $user->save();
        $this->assertTrue($user->Email === null);
    }

    public function testSavingRelatedObjects()
    {
        $user           = new \gnatUser();
        $user->name     = 'test';
        $email          = new \gnatEmail();
        $email->address = 'test3@test.com';
        $user->Email    = $email;
        $user->save();
        $this->assertTrue($user->Email instanceof \gnatEmail);
        $this->assertEquals($user->foreign_id, $user->Email->id);
    }
}
