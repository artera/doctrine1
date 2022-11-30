<?php
namespace Tests\Relation;

use Tests\DoctrineUnitTestCase;

class ParserTest extends DoctrineUnitTestCase
{
    public function testPendingRelations()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('User'));

        $p = ['type'  => \Doctrine1\Relation::ONE,
                   'local' => 'email_id'];

        $r->bind('Email', $p);

        $this->assertEquals(
            $r->getPendingRelation('Email'),
            ['type'  => \Doctrine1\Relation::ONE,
                'local' => 'email_id',
                'class' => 'Email',
                'alias' => 'Email'
            ]
        );
    }
    public function testBindThrowsExceptionIfTypeNotSet()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('User'));

        $p = ['local' => 'email_id'];
        $this->expectException(\Doctrine1\Relation\Exception::class);
        $r->bind('Email', $p);
    }
    public function testRelationParserSupportsLocalColumnGuessing()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('User'));

        $d = $r->completeDefinition(
            ['class'   => 'Phonenumber',
            'type'    => \Doctrine1\Relation::MANY,
            'foreign' => 'entity_id']
        );

        $this->assertEquals($d['local'], 'id');
    }
    public function testRelationParserSupportsLocalColumnGuessing2()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('User'));

        $d = $r->completeDefinition(
            ['class'   => 'Email',
            'type'    => \Doctrine1\Relation::ONE,
            'foreign' => 'id']
        );

        $this->assertEquals($d['local'], 'email_id');
    }
    public function testRelationParserSupportsForeignColumnGuessing()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('User'));

        $d = $r->completeDefinition(
            ['class' => 'Phonenumber',
            'type'  => \Doctrine1\Relation::MANY,
            'local' => 'id']
        );

        $this->assertEquals($d['foreign'], 'entity_id');
    }
    public function testRelationParserSupportsForeignColumnGuessing2()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('User'));

        $d = $r->completeDefinition(
            ['class' => 'Email',
            'type'  => \Doctrine1\Relation::ONE,
            'local' => 'email_id']
        );

        $this->assertEquals($d['foreign'], 'id');
    }
    public function testRelationParserSupportsGuessingOfBothColumns()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('User'));

        $d = $r->completeDefinition(
            ['class' => 'Email',
            'type'  => \Doctrine1\Relation::ONE]
        );

        $this->assertEquals($d['foreign'], 'id');
        $this->assertEquals($d['local'], 'email_id');
    }

    public function testRelationParserSupportsGuessingOfBothColumns2()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('User'));

        $d = $r->completeDefinition(
            ['class' => 'Phonenumber',
            'type'  => \Doctrine1\Relation::MANY]
        );

        $this->assertEquals($d['foreign'], 'entity_id');
        $this->assertEquals($d['local'], 'id');
    }
    public function testRelationParserSupportsForeignColumnGuessingForAssociations()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('User'));

        $d = $r->completeAssocDefinition(
            ['class'    => 'Group',
            'type'     => \Doctrine1\Relation::MANY,
            'local'    => 'user_id',
            'refClass' => 'GroupUser']
        );

        $this->assertEquals($d['foreign'], 'group_id');
    }
    public function testRelationParserSupportsLocalColumnGuessingForAssociations()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('User'));

        $d = $r->completeAssocDefinition(
            ['class'    => 'Group',
            'type'     => \Doctrine1\Relation::MANY,
            'foreign'  => 'group_id',
            'refClass' => 'GroupUser']
        );

        $this->assertEquals($d['local'], 'user_id');
    }
    public function testGetRelationReturnsForeignKeyObjectForOneToOneRelation()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('User'));
        $p = ['type'  => \Doctrine1\Relation::ONE,
                   'local' => 'email_id'];

        $r->bind('Email', $p);

        $rel = $r->getRelation('Email');

        $this->assertTrue($rel instanceof \Doctrine1\Relation\LocalKey);
    }
    public function testGetRelationReturnsForeignKeyObjectForOneToManyRelation()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('User'));
        $p = ['type' => \Doctrine1\Relation::MANY];

        $r->bind('Phonenumber', $p);

        $rel = $r->getRelation('Phonenumber');

        $this->assertTrue($rel instanceof \Doctrine1\Relation\ForeignKey);
    }
    public function testGetRelationReturnsForeignKeyObjectForManytToManyRelation()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('User'));
        $p = ['type'     => \Doctrine1\Relation::MANY,
                   'refClass' => 'GroupUser'];

        $r->bind('Group', $p);

        $rel = $r->getRelation('Group');

        $this->assertTrue($rel instanceof \Doctrine1\Relation\Association);
        $rel = $r->getRelation('GroupUser');
        $this->assertTrue($rel instanceof \Doctrine1\Relation\ForeignKey);
    }
    public function testGetRelationReturnsForeignKeyObjectForNestRelation()
    {
        $r = new \Doctrine1\Relation\Parser(static::$conn->getTable('Entity'));
        $p = ['type'     => \Doctrine1\Relation::MANY,
                   'refClass' => 'EntityReference',
                   'local'    => 'entity1',
                   'foreign'  => 'entity2'];

        $r->bind('Entity', $p);

        $rel = $r->getRelation('Entity');
        $this->assertTrue($rel instanceof \Doctrine1\Relation\Nest);

        $rel = $r->getRelation('EntityReference');
        $this->assertTrue($rel instanceof \Doctrine1\Relation\ForeignKey);
    }
}
