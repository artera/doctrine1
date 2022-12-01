<?php

namespace Tests\Core {
    use Tests\DoctrineUnitTestCase;

    class CollectionTest extends DoctrineUnitTestCase
    {
        public function testLoadRelatedForAssociation()
        {
            $coll = static::$connection->query('FROM User');

            $this->assertEquals($coll->count(), 8);

            $coll[0]->Group[1]->name = 'Actors House 2';

            $coll[0]->Group[2]->name = 'Actors House 3';

            $coll[2]->Group[0]->name = 'Actors House 4';
            $coll[2]->Group[1]->name = 'Actors House 5';
            $coll[2]->Group[2]->name = 'Actors House 6';

            $coll[5]->Group[0]->name = 'Actors House 7';
            $coll[5]->Group[1]->name = 'Actors House 8';
            $coll[5]->Group[2]->name = 'Actors House 9';

            $coll->save();

            static::$connection->clear();

            $coll = static::$connection->query('FROM User');

            $this->assertCount(8, $coll);
            $this->assertCount(2, $coll[0]->Group);
            $this->assertCount(1, $coll[1]->Group);
            $this->assertCount(3, $coll[2]->Group);
            $this->assertCount(3, $coll[5]->Group);

            static::$connection->clear();

            $coll = static::$connection->query('FROM User');

            $this->assertCount(8, $coll);

            $count = static::$connection->count();

            $coll->loadRelated('Group');
            $this->assertCount($count + 1, static::$connection);
            $this->assertCount(2, $coll[0]->Group);
            $this->assertCount($count + 1, static::$connection);
            $this->assertCount(1, $coll[1]->Group);

            $this->assertCount($count + 1, static::$connection);

            $this->assertCount(3, $coll[2]->Group);

            $this->assertCount($count + 1, static::$connection);
            $this->assertCount(3, $coll[5]->Group);

            $this->assertCount($count + 1, static::$connection);

            static::$connection->clear();
        }

        public function testOffsetGetWithNullArgumentReturnsNewRecord()
        {
            $coll = new \Doctrine1\Collection('User');
            $this->assertEquals($coll->count(), 0);

            $coll[]->name = 'zYne';

            $this->assertEquals($coll->count(), 1);
            $this->assertEquals($coll[0]->name, 'zYne');
        }

        public function testLoadRelatedForNormalAssociation()
        {
            $resource                   = new \Doctrine1\Collection('Resource');
            $resource[0]->name          = 'resource 1';
            $resource[0]->Type[0]->type = 'type 1';
            $resource[0]->Type[1]->type = 'type 2';
            $resource[1]->name          = 'resource 2';
            $resource[1]->Type[0]->type = 'type 3';
            $resource[1]->Type[1]->type = 'type 4';

            $resource->save();

            static::$connection->clear();

            $resources = static::$connection->query('FROM Resource');

            $count = static::$connection->count();
            $resources->loadRelated('Type');

            $this->assertEquals(($count + 1), static::$connection->count());
            $this->assertEquals($resources[0]->name, 'resource 1');
            $this->assertEquals($resource[0]->Type[0]->type, 'type 1');
            $this->assertEquals($resource[0]->Type[1]->type, 'type 2');
            $this->assertEquals(($count + 1), static::$connection->count());

            $this->assertEquals($resource[1]->name, 'resource 2');
            $this->assertEquals($resource[1]->Type[0]->type, 'type 3');
            $this->assertEquals($resource[1]->Type[1]->type, 'type 4');
            $this->assertEquals(($count + 1), static::$connection->count());
        }

        public function testAdd()
        {
            $coll = new \Doctrine1\Collection(static::$connection->getTable('User'));
            $coll->add(new \User());
            $this->assertEquals($coll->count(), 1);
            $coll->add(new \User());
            $this->assertEquals($coll->count(), 2);

            $this->assertEquals($coll->getKeys(), [0,1]);

            $coll[2] = new \User();

            $this->assertEquals($coll->count(), 3);
            $this->assertEquals($coll->getKeys(), [0,1,2]);
        }

        public function testLoadRelated()
        {
            $coll = static::$connection->query('FROM User u');

            $q = $coll->loadRelated();

            $this->assertTrue($q instanceof \Doctrine1\Query);

            $q->addFrom('User.Group g');

            $this->assertEquals($q->getSqlQuery($coll->getPrimaryKeys()), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id FROM entity e LEFT JOIN group_user g ON (e.id = g.user_id) LEFT JOIN entity e2 ON e2.id = g.group_id AND e2.type = 1 WHERE (e.id IN (?, ?, ?, ?, ?, ?, ?, ?) AND (e.type = 0))');

            $coll2 = $q->execute($coll->getPrimaryKeys());
            $this->assertEquals($coll2->count(), $coll->count());

            $count = static::$connection->count();
            $coll[0]->Group[0];
            $this->assertEquals($count, static::$connection->count());
        }

        public function testLoadRelatedForLocalKeyRelation()
        {
            $coll = static::$connection->query('FROM User');

            $this->assertEquals($coll->count(), 8);

            $count = static::$connection->count();
            $coll->loadRelated('Email');

            $this->assertEquals(($count + 1), static::$connection->count());

            $this->assertEquals($coll[0]->Email->address, 'zYne@example.com');

            $this->assertEquals(($count + 1), static::$connection->count());

            $this->assertEquals($coll[2]->Email->address, 'caine@example.com');

            $this->assertEquals($coll[3]->Email->address, 'kitano@example.com');

            $this->assertEquals($coll[4]->Email->address, 'stallone@example.com');

            $this->assertEquals(($count + 1), static::$connection->count());

            static::$connection->clear();
        }

        public function testLoadRelatedForForeignKey()
        {
            $coll = static::$connection->query('FROM User');
            $this->assertEquals($coll->count(), 8);

            $count = static::$connection->count();
            $coll->loadRelated('Phonenumber');

            $this->assertEquals(($count + 1), static::$connection->count());

            $this->assertEquals($coll[0]->Phonenumber[0]->phonenumber, '123 123');

            $this->assertEquals(($count + 1), static::$connection->count());

            $coll[0]->Phonenumber[1]->phonenumber;

            $this->assertEquals(($count + 1), static::$connection->count());

            $this->assertEquals($coll[4]->Phonenumber[0]->phonenumber, '111 555 333');
            $this->assertEquals($coll[4]['Phonenumber'][1]->phonenumber, '123 213');
            $this->assertEquals($coll[4]['Phonenumber'][2]->phonenumber, '444 555');

            $this->assertEquals($coll[5]->Phonenumber[0]->phonenumber, '111 222 333');


            $this->assertEquals($coll[6]->Phonenumber[0]->phonenumber, '111 222 333');
            $this->assertEquals($coll[6]['Phonenumber'][1]->phonenumber, '222 123');
            $this->assertEquals($coll[6]['Phonenumber'][2]->phonenumber, '123 456');

            $this->assertEquals(($count + 1), static::$connection->count());

            static::$connection->clear();
        }

        public function testCount()
        {
            $coll = new \Doctrine1\Collection(static::$connection->getTable('User'));
            $this->assertEquals($coll->count(), 0);
            $coll[0];
            $this->assertEquals($coll->count(), 1);
        }

        public function testFetchCollectionWithIdAsIndex()
        {
            $user = new \User();
            $user->setCollectionKey('id');

            $users = $user->getTable()->findAll();
            $this->assertFalse($users->contains(0));
            $this->assertEquals($users->count(), 8);
        }

        public function testFetchCollectionWithNameAsIndex()
        {
            $user = new \User();
            $user->setCollectionKey('name');

            $users = $user->getTable()->findAll();
            $this->assertFalse($users->contains(0));
            $this->assertEquals($users->count(), 8);
        }

        public function testFetchMultipleCollections()
        {
            static::$connection->clear();

            $user = new \User();
            $user->setCollectionKey('id');
            $phonenumber = new \Phonenumber();
            $phonenumber->setCollectionKey('id');


            $q     = new \Doctrine1\Query();
            $users = $q->from('User u, u.Phonenumber p')->execute();
            $this->assertFalse($users->contains(0));
            $this->assertEquals($users->count(), 8);

            $this->assertEquals($users[4]->name, 'zYne');

            $this->assertEquals($users[4]->Phonenumber[0]->exists(), false);
            $this->assertEquals($users[4]->Phonenumber[1]->exists(), false);
        }

        public function testCustomManagerCollectionClass()
        {
            $manager = \Doctrine1\Manager::getInstance();
            $manager->setCollectionClass(\MyCollection::class);

            $user = new \User();
            $this->assertTrue($user->Phonenumber instanceof \MyCollection);

            $manager->setCollectionClass(\Doctrine1\Collection::class);
        }

        public function testCustomConnectionCollectionClass()
        {
            $conn = \Doctrine1\Core::getTable('Phonenumber')->getConnection();
            $conn->setCollectionClass(\MyConnectionCollection::class);

            $user = new \User();
            $this->assertTrue($user->Phonenumber instanceof \MyConnectionCollection);

            $conn->setCollectionClass(null);
        }

        public function testCustomTableCollectionClass()
        {
            $userTable = \Doctrine1\Core::getTable('Phonenumber');
            $userTable->setCollectionClass(\MyPhonenumberCollection::class);

            $user = new \User();
            $this->assertTrue($user->Phonenumber instanceof \MyPhonenumberCollection);

            $userTable->setCollectionClass(null);
        }
    }
}

namespace {
    class MyCollection extends \Doctrine1\Collection
    {
    }

    class MyConnectionCollection extends MyCollection
    {
    }

    class MyPhonenumberCollection extends MyConnectionCollection
    {
    }
}
