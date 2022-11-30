<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket424BTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['MmrUserB', 'MmrGroupB', 'MmrGroupUserB'];

    public static function prepareData(): void
    {
    }

    protected function newGroup($code, $name)
    {
        $group       = new \MmrGroupB();
        $group->id   = $code;
        $group->name = $name;
        $group->save();
        return $group;
    }

    protected function newUser($code, $name, $groups)
    {
        $u       = new \MmrUserB();
        $u->id   = $code;
        $u->name = $name;

        foreach ($groups as $idx => $group) {
            $u->Group[$idx] = $group;
        }

        $u->save();
        return $u;
    }

    public function testManyManyRelationWithAliasColumns()
    {
        $groupA = $this->newGroup(1, 'Group A');
        $groupB = $this->newGroup(2, 'Group B');
        $groupC = $this->newGroup(3, 'Group C');

        $john  = $this->newUser(1, 'John', [$groupA, $groupB]);
        $peter = $this->newUser(2, 'Peter', [$groupA, $groupC]);
        $alan  = $this->newUser(3, 'Alan', [$groupB, $groupC]);

        $q  = \Doctrine1\Query::create();
        $gu = $q->from('MmrGroupUserB')->execute();
        $this->assertEquals(count($gu), 6);

        // Direct query
        $q  = \Doctrine1\Query::create();
        $gu = $q->from('MmrGroupUserB')->where('group_id = ?', $groupA->id)->execute();
        $this->assertEquals(count($gu), 2);

        // Query by join
        $q = \Doctrine1\Query::create()
            ->from('MmrUserB u, u.Group g')
            ->where('g.name = ?', [$groupA->name]);

        $userOfGroupAByName = $q->execute();

        $this->assertEquals(count($userOfGroupAByName), 2);
    }
}
