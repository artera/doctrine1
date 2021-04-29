<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket1808Test extends DoctrineUnitTestCase
{
    public function testTest()
    {
        $userTable = \Doctrine_Core::getTable('User');

        $this->assertEquals($userTable->buildFindByWhere('NameAndPassword'), 'dctrn_find.name = ? AND dctrn_find.password = ?');
        $this->assertEquals($userTable->buildFindByWhere('NameOrLoginname'), '(dctrn_find.name = ? OR dctrn_find.loginname = ?)');
        $this->assertEquals($userTable->buildFindByWhere('NameAndPasswordOrLoginname'), 'dctrn_find.name = ? AND (dctrn_find.password = ? OR dctrn_find.loginname = ?)');
        $this->assertEquals($userTable->buildFindByWhere('NameAndPasswordOrLoginnameAndName'), 'dctrn_find.name = ? AND (dctrn_find.password = ? OR dctrn_find.loginname = ?) AND dctrn_find.name = ?');

        $user                 = new \User();
        $user->name           = 'bigtest';
        $user->loginname      = 'cooltest';
        $user->Email = new \Email();
        $user->Email->address = 'jonathan.wage@sensio.com';
        $user->save();

        $user2 = $userTable->findOneByNameAndLoginnameAndEmailId($user->name, $user->loginname, $user->email_id);
        $this->assertSame($user, $user2);

        $test = $userTable->findOneByNameAndLoginnameAndEmailId($user->name, $user->loginname, $user->email_id, hydrate_array: true);
        $this->assertTrue(is_array($test));
    }
}
