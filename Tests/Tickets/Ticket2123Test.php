<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket2123Test extends DoctrineUnitTestCase
{
    public function testCheckingRelatedExistsOnCollectionThrowsException()
    {
        $this->expectException(\Doctrine_Record_Exception::class);

        \Doctrine_Core::getTable('User')
            ->createQuery('u')
            ->fetchOne()
            ->relatedExists('Phonenumber');
    }

    public function testRelatedExistsClearsReference()
    {
        $user = new \User();
        $this->assertEquals($user->relatedExists('Email'), false);
        $this->assertEquals($user->hasReference('Email'), false);
    }

    public function testClearRelatedReference()
    {
        $user  = new \User();
        $email = $user->Email;
        $user->clearRelated('Email');
        $this->assertEquals($user->hasReference('Email'), false);
    }
}
