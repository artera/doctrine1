<?php
namespace Tests\Relation;

use Tests\DoctrineUnitTestCase;

class RelationTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    protected static array $tables = ['RelationTest', 'RelationTestChild', 'Group', 'Groupuser', 'User', 'Email', 'Account', 'Phonenumber'];

    public function testInitData()
    {
        $user = new \User();

        $user->name           = 'zYne';
        $user->Group[0]->name = 'Some Group';
        $user->Group[1]->name = 'Other Group';
        $user->Group[2]->name = 'Third Group';

        $user->Phonenumber[0]->phonenumber = '123 123';
        $user->Phonenumber[1]->phonenumber = '234 234';
        $user->Phonenumber[2]->phonenumber = '456 456';

        $user->Email = new \Email();
        $user->Email->address = 'someone@some.where';

        $user->save();
    }

    public function testUnlinkSupportsManyToManyRelations()
    {
        $users = \Doctrine1\Query::create()->from('User u')->where('u.name = ?', ['zYne'])->execute();

        $user = $users[0];

        $this->assertEquals($user->Group->count(), 3);

        $user->unlink('Group', [2, 3, 4], true);

        $this->assertEquals($user->Group->count(), 0);

        static::$conn->clear();

        $groups = \Doctrine1\Query::create()->from('Group g')->execute();

        $this->assertEquals($groups->count(), 3);

        $links = \Doctrine1\Query::create()->from('GroupUser gu')->execute();

        $this->assertEquals($links->count(), 0);
    }

    public function testUnlinkSupportsOneToManyRelations()
    {
        static::$conn->clear();

        $users = \Doctrine1\Query::create()->from('User u')->where('u.name = ?', ['zYne'])->execute();

        $user = $users[0];

        $this->assertEquals($user->Phonenumber->count(), 3);

        $user->unlink('Phonenumber', [1, 2, 3], true);

        $this->assertEquals($user->Phonenumber->count(), 0);

        static::$conn->clear();

        $phonenumber = \Doctrine1\Query::create()->from('Phonenumber p')->execute();

        $this->assertEquals($phonenumber->count(), 3);
        $this->assertEquals($phonenumber[0]->entity_id, null);
        $this->assertEquals($phonenumber[1]->entity_id, null);
        $this->assertEquals($phonenumber[2]->entity_id, null);
    }

    public function testOneToManyTreeRelationWithConcreteInheritance()
    {
        $component = new \RelationTestChild();

        $rel = $component->getTable()->getRelation('Children');

        $this->assertTrue($rel instanceof \Doctrine1\Relation\ForeignKey);

        $this->assertTrue($component->Children instanceof \Doctrine1\Collection);
        $this->assertTrue($component->Children[0] instanceof \RelationTestChild);
    }

    public function testOneToOneTreeRelationWithConcreteInheritance()
    {
        $component = new \RelationTestChild();

        $rel = $component->getTable()->getRelation('Parent');

        $this->assertTrue($rel instanceof \Doctrine1\Relation\LocalKey);
    }
    public function testManyToManyRelation()
    {
        $user = new \User();

        // test that join table relations can be initialized even before the association have been initialized
        $user->GroupUser;

        $this->assertTrue($user->getTable()->getRelation('Group') instanceof \Doctrine1\Relation\Association);
    }
    public function testOneToOneLocalKeyRelation()
    {
        $user = new \User();

        $this->assertTrue($user->getTable()->getRelation('Email') instanceof \Doctrine1\Relation\LocalKey);
    }
    public function testOneToOneForeignKeyRelation()
    {
        $user = new \User();

        $this->assertTrue($user->getTable()->getRelation('Account') instanceof \Doctrine1\Relation\ForeignKey);
    }
    public function testOneToManyForeignKeyRelation()
    {
        $user = new \User();

        $this->assertTrue($user->getTable()->getRelation('Phonenumber') instanceof \Doctrine1\Relation\ForeignKey);
    }
}
