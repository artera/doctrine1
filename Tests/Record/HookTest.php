<?php
namespace Tests\Record;

use Tests\DoctrineUnitTestCase;

class HookTest extends DoctrineUnitTestCase
{
    public function testWordLikeParserSupportsHyphens()
    {
        $parser = new \Doctrine1\Hook\WordLike();

        $parser->parse('u', 'name', "'some guy' OR zYne");

        $this->assertEquals($parser->getCondition(), '(u.name LIKE ? OR u.name LIKE ?)');
        $this->assertEquals($parser->getParams(), ['%some guy%', '%zYne%']);
    }

    public function testHookOrderbyAcceptsArray()
    {
        $hook = new \Doctrine1\Hook('SELECT u.name FROM User u LEFT JOIN u.Phonenumber p');

        $a['orderby'] = ['u.name ASC'];

        $hook->hookOrderBy($a['orderby']);
        $this->assertEquals($hook->getQuery()->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) ORDER BY e.name ASC');
    }

    public function testHookOrderbyAcceptsDescendingOrder()
    {
        $hook = new \Doctrine1\Hook('SELECT u.name FROM User u LEFT JOIN u.Phonenumber p');

        $a['orderby'] = ['u.name DESC'];

        $hook->hookOrderBy($a['orderby']);
        $this->assertEquals($hook->getQuery()->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) ORDER BY e.name DESC');
    }

    public function testHookOrderbyDoesntAcceptUnknownColumn()
    {
        $hook = new \Doctrine1\Hook('SELECT u.name FROM User u LEFT JOIN u.Phonenumber p');

        $a['orderby'] = ['u.unknown DESC'];

        $hook->hookOrderBy($a['orderby']);
        $this->assertEquals($hook->getQuery()->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }

    public function testHookOrderbyAcceptsMultipleParameters()
    {
        $hook = new \Doctrine1\Hook('SELECT u.name FROM User u LEFT JOIN u.Phonenumber p');

        $a['orderby'] = ['u.name ASC', 'u.id DESC'];

        $hook->hookOrderBy($a['orderby']);
        $this->assertEquals($hook->getQuery()->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) ORDER BY e.name ASC, e.id DESC');

        $users = $hook->getQuery()->execute();
    }

    public function testHookWhereAcceptsArrays()
    {
        $hook = new \Doctrine1\Hook('SELECT u.name FROM User u LEFT JOIN u.Phonenumber p');

        $a['where'] = ['u.name'      => 'Jack Daniels',
                            'u.loginname' => 'TheMan'];

        $hook->hookWhere($a['where']);
        $this->assertEquals($hook->getQuery()->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.name LIKE ? OR e.name LIKE ?) AND e.loginname LIKE ? AND (e.type = 0)');
        $this->assertEquals($hook->getQuery()->getFlattenedParams(), ['%Jack%', '%Daniels%', '%TheMan%']);
    }

    public function testHookWhereSupportsIntegerTypes()
    {
        $hook = new \Doctrine1\Hook('SELECT u.name FROM User u LEFT JOIN u.Phonenumber p');

        $a['where'] = ['u.id' => 10000];

        $hook->hookWhere($a['where']);
        $this->assertEquals($hook->getQuery()->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.id = ? AND (e.type = 0))');
        $this->assertEquals($hook->getQuery()->getFlattenedParams(), [10000]);
    }

    public function testHookWhereDoesntAcceptUnknownColumn()
    {
        $hook = new \Doctrine1\Hook('SELECT u.name FROM User u LEFT JOIN u.Phonenumber p');

        $a['where'] = ['u.unknown' => 'Jack Daniels'];

        $hook->hookWhere($a['where']);

        $this->assertEquals($hook->getQuery()->getSqlQuery(), 'SELECT e.id AS e__id, e.name AS e__name FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }

    public function testEqualParserUsesEqualOperator()
    {
        $parser = new \Doctrine1\Hook\Equal();

        $parser->parse('u', 'name', 'zYne');

        $this->assertEquals($parser->getCondition(), 'u.name = ?');
        $this->assertEquals($parser->getParams(), ['zYne']);
    }

    public function testWordLikeParserUsesLikeOperator()
    {
        $parser = new \Doctrine1\Hook\WordLike();

        $parser->parse('u', 'name', 'zYne');

        $this->assertEquals($parser->getCondition(), 'u.name LIKE ?');
        $this->assertEquals($parser->getParams(), ['%zYne%']);
    }

    public function testIntegerParserSupportsIntervals()
    {
        $parser = new \Doctrine1\Hook\Integer();

        $parser->parse('m', 'year', '1998-2000');

        $this->assertEquals($parser->getCondition(), '(m.year > ? AND m.year < ?)');
        $this->assertEquals($parser->getParams(), ['1998', '2000']);
    }

    public function testIntegerParserSupportsEqualOperator()
    {
        $parser = new \Doctrine1\Hook\Integer();

        $parser->parse('m', 'year', '1998');

        $this->assertEquals($parser->getCondition(), 'm.year = ?');
        $this->assertEquals($parser->getParams(), ['1998']);
    }

    public function testIntegerParserSupportsNestingConditions()
    {
        $parser = new \Doctrine1\Hook\Integer();

        $parser->parse('m', 'year', '1998-2000 OR 2001');

        $this->assertEquals($parser->getCondition(), '((m.year > ? AND m.year < ?) OR m.year = ?)');
        $this->assertEquals($parser->getParams(), ['1998', '2000', '2001']);
    }
}
