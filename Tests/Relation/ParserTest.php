<?php
namespace Tests\Relation;

use Tests\DoctrineUnitTestCase;

class ParserTest extends DoctrineUnitTestCase
{
    public function testPendingRelations()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('User'));

        $p = ['type'  => \Doctrine_Relation::ONE,
                   'local' => 'email_id'];

        $r->bind('Email', $p);

        $this->assertEquals(
            $r->getPendingRelation('Email'),
            ['type'  => \Doctrine_Relation::ONE,
                'local' => 'email_id',
                'class' => 'Email',
                'alias' => 'Email'
            ]
        );
    }
    public function testBindThrowsExceptionIfTypeNotSet()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('User'));

        $p = ['local' => 'email_id'];
        $this->expectException(\Doctrine_Relation_Exception::class);
        $r->bind('Email', $p);
    }
    public function testRelationParserSupportsLocalColumnGuessing()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('User'));

        $d = $r->completeDefinition(
            ['class'   => 'Phonenumber',
            'type'    => \Doctrine_Relation::MANY,
            'foreign' => 'entity_id']
        );

        $this->assertEquals($d['local'], 'id');
    }
    public function testRelationParserSupportsLocalColumnGuessing2()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('User'));

        $d = $r->completeDefinition(
            ['class'   => 'Email',
            'type'    => \Doctrine_Relation::ONE,
            'foreign' => 'id']
        );

        $this->assertEquals($d['local'], 'email_id');
    }
    public function testRelationParserSupportsForeignColumnGuessing()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('User'));

        $d = $r->completeDefinition(
            ['class' => 'Phonenumber',
            'type'  => \Doctrine_Relation::MANY,
            'local' => 'id']
        );

        $this->assertEquals($d['foreign'], 'entity_id');
    }
    public function testRelationParserSupportsForeignColumnGuessing2()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('User'));

        $d = $r->completeDefinition(
            ['class' => 'Email',
            'type'  => \Doctrine_Relation::ONE,
            'local' => 'email_id']
        );

        $this->assertEquals($d['foreign'], 'id');
    }
    public function testRelationParserSupportsGuessingOfBothColumns()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('User'));

        $d = $r->completeDefinition(
            ['class' => 'Email',
            'type'  => \Doctrine_Relation::ONE]
        );

        $this->assertEquals($d['foreign'], 'id');
        $this->assertEquals($d['local'], 'email_id');
    }

    public function testRelationParserSupportsGuessingOfBothColumns2()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('User'));

        $d = $r->completeDefinition(
            ['class' => 'Phonenumber',
            'type'  => \Doctrine_Relation::MANY]
        );

        $this->assertEquals($d['foreign'], 'entity_id');
        $this->assertEquals($d['local'], 'id');
    }
    public function testRelationParserSupportsForeignColumnGuessingForAssociations()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('User'));

        $d = $r->completeAssocDefinition(
            ['class'    => 'Group',
            'type'     => \Doctrine_Relation::MANY,
            'local'    => 'user_id',
            'refClass' => 'GroupUser']
        );

        $this->assertEquals($d['foreign'], 'group_id');
    }
    public function testRelationParserSupportsLocalColumnGuessingForAssociations()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('User'));

        $d = $r->completeAssocDefinition(
            ['class'    => 'Group',
            'type'     => \Doctrine_Relation::MANY,
            'foreign'  => 'group_id',
            'refClass' => 'GroupUser']
        );

        $this->assertEquals($d['local'], 'user_id');
    }
    public function testGetRelationReturnsForeignKeyObjectForOneToOneRelation()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('User'));
        $p = ['type'  => \Doctrine_Relation::ONE,
                   'local' => 'email_id'];

        $r->bind('Email', $p);

        $rel = $r->getRelation('Email');

        $this->assertTrue($rel instanceof \Doctrine_Relation_LocalKey);
    }
    public function testGetRelationReturnsForeignKeyObjectForOneToManyRelation()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('User'));
        $p = ['type' => \Doctrine_Relation::MANY];

        $r->bind('Phonenumber', $p);

        $rel = $r->getRelation('Phonenumber');

        $this->assertTrue($rel instanceof \Doctrine_Relation_ForeignKey);
    }
    public function testGetRelationReturnsForeignKeyObjectForManytToManyRelation()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('User'));
        $p = ['type'     => \Doctrine_Relation::MANY,
                   'refClass' => 'GroupUser'];

        $r->bind('Group', $p);

        $rel = $r->getRelation('Group');

        $this->assertTrue($rel instanceof \Doctrine_Relation_Association);
        $rel = $r->getRelation('GroupUser');
        $this->assertTrue($rel instanceof \Doctrine_Relation_ForeignKey);
    }
    public function testGetRelationReturnsForeignKeyObjectForNestRelation()
    {
        $r = new \Doctrine_Relation_Parser(static::$conn->getTable('Entity'));
        $p = ['type'     => \Doctrine_Relation::MANY,
                   'refClass' => 'EntityReference',
                   'local'    => 'entity1',
                   'foreign'  => 'entity2'];

        $r->bind('Entity', $p);

        $rel = $r->getRelation('Entity');
        $this->assertTrue($rel instanceof \Doctrine_Relation_Nest);

        $rel = $r->getRelation('EntityReference');
        $this->assertTrue($rel instanceof \Doctrine_Relation_ForeignKey);
    }
}
