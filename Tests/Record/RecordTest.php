<?php
namespace Tests\Record;

use Tests\DoctrineUnitTestCase;

class RecordTest extends DoctrineUnitTestCase
{
    protected static array $tables = [
        'enumTest',
        'fieldNameTest',
        'GzipTest',
        'Book',
        'EntityAddress',
        'UnderscoreColumn',
        'NotNullTest',
    ];

    public function testOne2OneForeign()
    {
        static::$conn->clear();
        $user       = new \User();
        $user->name = 'Richard Linklater';

        $rel = $user->getTable()->getRelation('Account');

        $this->assertTrue($rel instanceof \Doctrine_Relation_ForeignKey);

        $account         = $user->Account;
        $account->amount = 1000;
        $this->assertTrue($account instanceof \Account);
        $this->assertEquals($account->state(), \Doctrine_Record_State::TDIRTY());
        $this->assertEquals($account->entity_id->getOid(), $user->getOid());
        $this->assertEquals($account->amount, 1000);
        $this->assertEquals($user->name, 'Richard Linklater');

        $user->save();
        $this->assertEquals($account->entity_id, $user->id);

        $user->refresh();

        $account = $user->Account;
        $this->assertTrue($account instanceof \Account);
        $this->assertEquals($account->state(), \Doctrine_Record_State::CLEAN());
        $this->assertEquals($account->entity_id, $user->id);
        $this->assertEquals($account->amount, 1000);
        $this->assertEquals($user->name, 'Richard Linklater');


        $user            = new \User();
        $user->name      = 'John Rambo';
        $account         = $user->Account;
        $account->amount = 2000;
        $this->assertEquals($account->getTable()->getColumnNames(), ['id', 'entity_id', 'amount']);

        static::$connection->flush();
        $this->assertEquals($user->state(), \Doctrine_Record_State::CLEAN());
        $this->assertTrue($account instanceof \Account);

        $this->assertEquals($account->getTable()->getColumnNames(), ['id', 'entity_id', 'amount']);
        $this->assertEquals($account->entity_id, $user->id);
        $this->assertEquals($account->amount, 2000);


        $user = $user->getTable()->find($user->id);
        $this->assertEquals($user->state(), \Doctrine_Record_State::CLEAN());


        $account = $user->Account;
        $this->assertTrue($account instanceof \Account);

        $this->assertEquals($account->state(), \Doctrine_Record_State::CLEAN());
        $this->assertEquals($account->getTable()->getColumnNames(), ['id', 'entity_id', 'amount']);

        $this->assertEquals($account->entity_id, $user->id);
        $this->assertEquals($account->amount, 2000);
        $this->assertEquals($user->name, 'John Rambo');
    }

    public function testNotNullConstraint()
    {
        $null = new \NotNullTest();

        $null->name = null;

        $null->type = 1;

        $this->expectException(\Doctrine_Connection_Exception::class);
        $null->save();
    }

    public function testGzipType()
    {
        $gzip       = new \GzipTest();
        $gzip->gzip = 'compressed';

        $this->assertEquals($gzip->gzip, 'compressed');
        $gzip->save();
        $this->assertEquals($gzip->gzip, 'compressed');
        $gzip->refresh();
        $this->assertEquals($gzip->gzip, 'compressed');

        static::$connection->clear();
        $gzip = $gzip->getTable()->find($gzip->id);
        $this->assertEquals($gzip->gzip, 'compressed');

        $gzip->gzip = 'compressed 2';

        $this->assertEquals($gzip->gzip, 'compressed 2');
        $gzip->save();
        $this->assertEquals($gzip->gzip, 'compressed 2');
        $gzip->refresh();
        $this->assertEquals($gzip->gzip, 'compressed 2');
    }

    public function testDefaultValues()
    {
        $test = new \FieldNameTest;

        $this->assertEquals($test->someColumn, 'some string');
        $this->assertEquals($test->someEnum, 'php');
        $this->assertEquals($test->someArray, []);
        $this->assertTrue(is_object($test->someObject));
        $this->assertEquals($test->someInt, 11);
    }


    public function testToArray()
    {
        $query = \Doctrine_Query::create()
            ->select('u.id')
            ->from('User u')
            ->limit(1);
        $user = $query->fetchOne()->toArray();
        $this->assertFalse((bool) $user['name']);

        $user = new \User();

        $a = $user->toArray();

        $this->assertTrue(is_array($a));
        $this->assertTrue(array_key_exists('name', $a));


        $this->assertEquals($a['name'], null);
        $this->assertTrue(array_key_exists('id', $a));
        $this->assertEquals($a['id'], null);

        $user->name = 'Someone';

        $user->save();

        $a = $user->toArray();

        $this->assertTrue(is_array($a));
        $this->assertTrue(array_key_exists('name', $a));
        $this->assertEquals($a['name'], 'Someone');
        $this->assertTrue(array_key_exists('id', $a));
        $this->assertTrue(is_numeric($a['id']));

        $user->refresh();

        $a = $user->toArray();

        $this->assertTrue(is_array($a));
        $this->assertTrue(array_key_exists('name', $a));
        $this->assertEquals($a['name'], 'Someone');
        $this->assertTrue(array_key_exists('id', $a));
        $this->assertTrue(is_numeric($a['id']));
        static::$connection->clear();
        $user = $user->getTable()->find($user->id);

        $a = $user->toArray();

        $this->assertTrue(is_array($a));
        $this->assertTrue(array_key_exists('name', $a));
        $this->assertEquals($a['name'], 'Someone');
        $this->assertTrue(array_key_exists('id', $a));
        $this->assertTrue(is_numeric($a['id']));
    }

    public function testReferences2()
    {
        $user                              = new \User();
        $user->Phonenumber[0]->phonenumber = '123 123';
        $ref                               = $user->Phonenumber[0]->entity_id;

        $this->assertEquals($ref->getOid(), $user->getOid());
    }

    public function testUpdatingWithNullValue()
    {
        $user       = static::$connection->getTable('User')->find(5);
        $user->name = null;
        $this->assertEquals($user->name, null);

        $user->save();

        $this->assertEquals($user->name, null);

        static::$connection->clear();

        $user = static::$connection->getTable('User')->find(5);

        $this->assertEquals($user->name, null);
    }

    public function testSerialize()
    {
        $user  = static::$connection->getTable('User')->find(4);
        $str   = serialize($user);
        $user2 = unserialize($str);

        $this->assertTrue($user2 instanceof \User);
        $this->assertEquals($user2->identifier(), $user->identifier());
    }

    public function testCallback()
    {
        $user       = new \User();
        $user->name = ' zYne ';
        $user->call('trim', 'name');
        $this->assertEquals($user->name, 'zYne');
        $user->call('substr', 'name', 0, 1);
        $this->assertEquals($user->name, 'z');
    }

    public function testCompositePK()
    {
        $record = new \EntityReference();
        $this->assertEquals($record->getTable()->getIdentifier(), ['entity1','entity2']);
        $this->assertEquals($record->getTable()->getIdentifierType(), \Doctrine_Core::IDENTIFIER_COMPOSITE);
        $this->assertEquals($record->identifier(), ['entity1' => null, 'entity2' => null]);
        $this->assertEquals($record->state(), \Doctrine_Record_State::TCLEAN());

        $record->entity1 = 3;
        $record->entity2 = 4;
        $this->assertEquals($record->entity2, 4);
        $this->assertEquals($record->entity1, 3);
        $this->assertEquals($record->state(), \Doctrine_Record_State::TDIRTY());
        $this->assertEquals($record->identifier(), ['entity1' => null, 'entity2' => null]);

        $record->save();
        $this->assertEquals($record->state(), \Doctrine_Record_State::CLEAN());
        $this->assertEquals($record->entity2, 4);
        $this->assertEquals($record->entity1, 3);
        $this->assertEquals($record->identifier(), ['entity1' => 3, 'entity2' => 4]);

        $record = $record->getTable()->find($record->identifier());
        $this->assertEquals($record->state(), \Doctrine_Record_State::CLEAN());
        $this->assertEquals($record->entity2, 4);
        $this->assertEquals($record->entity1, 3);

        $this->assertEquals($record->identifier(), ['entity1' => 3, 'entity2' => 4]);

        $record->entity2 = 5;
        $record->entity1 = 2;
        $this->assertEquals($record->state(), \Doctrine_Record_State::DIRTY());
        $this->assertEquals($record->entity2, 5);
        $this->assertEquals($record->entity1, 2);
        $this->assertEquals($record->identifier(), ['entity1' => 3, 'entity2' => 4]);

        $record->save();
        $this->assertEquals($record->state(), \Doctrine_Record_State::CLEAN());
        $this->assertEquals($record->entity2, 5);
        $this->assertEquals($record->entity1, 2);
        $this->assertEquals($record->identifier(), ['entity1' => 2, 'entity2' => 5]);
        $record = $record->getTable()->find($record->identifier());

        $this->assertEquals($record->state(), \Doctrine_Record_State::CLEAN());
        $this->assertEquals($record->entity2, 5);
        $this->assertEquals($record->entity1, 2);
        $this->assertEquals($record->identifier(), ['entity1' => 2, 'entity2' => 5]);

        $record->refresh();
        $this->assertEquals($record->state(), \Doctrine_Record_State::CLEAN());
        $this->assertEquals($record->entity2, 5);
        $this->assertEquals($record->entity1, 2);
        $this->assertEquals($record->identifier(), ['entity1' => 2, 'entity2' => 5]);

        $record          = new \EntityReference();
        $record->entity2 = 6;
        $record->entity1 = 2;
        $record->save();

        $coll = static::$connection->query('FROM EntityReference');
        $this->assertTrue($coll[0] instanceof \EntityReference);
        $this->assertEquals($coll[0]->state(), \Doctrine_Record_State::CLEAN());
        $this->assertTrue($coll[1] instanceof \EntityReference);
        $this->assertEquals($coll[1]->state(), \Doctrine_Record_State::CLEAN());

        $coll = static::$connection->query('FROM EntityReference WHERE EntityReference.entity2 = 5');
        $this->assertEquals($coll->count(), 1);
    }

    public function testManyToManyTreeStructure()
    {
        static::$conn->clear();
        $task = static::$connection->create('Task');

        $task->name                   = 'Task 1';
        $task->ResourceAlias[0]->name = 'Resource 1';

        static::$connection->flush();

        $this->assertTrue($task->ResourceAlias[0] instanceof \Resource);
        $this->assertEquals($task->ResourceAlias[0]->name, 'Resource 1');
        $this->assertEquals(static::$dbh->query('SELECT COUNT(*) FROM assignment')->fetch(\PDO::FETCH_NUM), [1]);

        $task = new \Task();
        $this->assertTrue($task instanceof \Task);
        $this->assertEquals($task->state(), \Doctrine_Record_State::TCLEAN());
        $this->assertTrue($task->Subtask[0] instanceof \Task);

        //$this->assertEquals($task->Subtask[0]->state(), \Doctrine_Record_State::TDIRTY());
        $this->assertTrue($task->ResourceAlias[0] instanceof \Resource);
        $this->assertEquals($task->ResourceAlias[0]->state(), \Doctrine_Record_State::TCLEAN());

        $task->name                   = 'Task 1';
        $task->ResourceAlias[0]->name = 'Resource 1';
        $task->Subtask[0]->name       = 'Subtask 1';

        $this->assertEquals($task->name, 'Task 1');
        $this->assertEquals($task->ResourceAlias[0]->name, 'Resource 1');
        $this->assertEquals($task->ResourceAlias->count(), 1);
        $this->assertEquals($task->Subtask[0]->name, 'Subtask 1');

        static::$connection->flush();

        $task = $task->getTable()->find($task->identifier());

        $this->assertEquals($task->name, 'Task 1');
        $this->assertEquals($task->ResourceAlias[0]->name, 'Resource 1');
        $this->assertEquals($task->ResourceAlias->count(), 1);
        $this->assertEquals($task->Subtask[0]->name, 'Subtask 1');
    }


    public function testGet()
    {
        $user       = new \User();
        $user->name = 'Jack Daniels';
        $this->assertEquals($user->name, 'Jack Daniels');
        $this->assertEquals($user->created, null);
        $this->assertEquals($user->updated, null);
        $user->save();
        $id   = $user->identifier();
        $user = $user->getTable()->find($id);
        $this->assertEquals($user->name, 'Jack Daniels');
        $this->assertEquals($user->created, null);
        $this->assertEquals($user->updated, null);
        $this->assertEquals($user->getTable()->getData(), []);
    }


    public function testUnknownFieldGet()
    {
        $user       = new \User();
        $user->name = 'Jack Daniels';
        $user->save();

        $this->expectException(\Doctrine_Record_UnknownPropertyException::class);
        $foo = $user->unexistentColumnInThisClass;
    }


    public function testUnknownFieldGet2()
    {
        $user       = new \User();
        $user->name = 'Jack Daniels';
        $user->save();

        $this->expectException(\Doctrine_Record_UnknownPropertyException::class);
        $foo = $user->get('unexistentColumnInThisClass');
    }


    public function testNewOperator()
    {
        $table = static::$connection->getTable('User');

        $this->assertEquals(static::$connection->getTable('User')->getData(), []);
        $user = new \User();
        $this->assertEquals(5, $user->state()->getValue());
        $user->name = 'John Locke';

        $this->assertEquals('John Locke', $user->name);
        $this->assertEquals(\Doctrine_Record_State::TDIRTY(), $user->state());
        $user->save();
        $this->assertEquals(\Doctrine_Record_State::CLEAN(), $user->state());
        $this->assertEquals('John Locke', $user->name);
    }

    public function testTreeStructure()
    {
        static::$conn->clear();
        $e = new \Element();

        $fk = $e->getTable()->getRelation('Child');
        $this->assertTrue($fk instanceof \Doctrine_Relation_ForeignKey);
        $this->assertEquals($fk->getType(), \Doctrine_Relation::MANY);
        $this->assertEquals($fk->getForeign(), 'parent_id');
        $this->assertEquals($fk->getLocal(), 'id');



        $e->name           = 'parent';
        $e->Child[0]->name = 'child 1';
        $e->Child[1]->name = 'child 2';

        $e->Child[1]->Child[0]->name = "child 1's child 1";
        $e->Child[1]->Child[1]->name = "child 1's child 1";

        $this->assertEquals($e->name, 'parent');

        $this->assertEquals($e->Child[0]->name, 'child 1');
        $this->assertEquals($e->Child[1]->name, 'child 2');
        $this->assertEquals($e->Child[1]->Child[0]->name, "child 1's child 1");
        $this->assertEquals($e->Child[1]->Child[1]->name, "child 1's child 1");



        static::$connection->flush();
        $elements = static::$connection->query('FROM Element');
        $this->assertEquals($elements->count(), 5);

        $e = $e->getTable()->find(1);
        $this->assertEquals($e->name, 'parent');

        $this->assertEquals($e->Child[0]->name, 'child 1');

        $c = $e->getTable()->find(2);
        $this->assertEquals($c->name, 'child 1');

        $this->assertEquals($e->Child[0]->parent_id, 1);
        $this->assertEquals($e->Child[0]->Parent->identifier(), $e->identifier());


        $this->assertEquals($e->Child[1]->parent_id, 1);
        $this->assertEquals($e->Child[1]->Child[0]->name, "child 1's child 1");
        $this->assertEquals($e->Child[1]->Child[1]->name, "child 1's child 1");
        $this->assertEquals($e->Child[1]->Child[0]->parent_id, 3);
        $this->assertEquals($e->Child[1]->Child[1]->parent_id, 3);
    }

    public function testUniqueKeyComponent()
    {
        $e           = new \TestError();
        $e->message  = 'user error';
        $e->file_md5 = md5(0);
        $e->code     = 1;

        // ADDING NEW \RECORD
        $this->assertEquals($e->code, 1);
        $this->assertEquals($e->file_md5, md5(0));
        $this->assertEquals($e->message, 'user error');

        $e2           = new \TestError();
        $e2->message  = 'user error2';
        $e2->file_md5 = md5(1);
        $e2->code     = 2;

        $this->assertEquals($e2->code, 2);
        $this->assertEquals($e2->file_md5, md5(1));
        $this->assertEquals($e2->message, 'user error2');


        $fk = $e->getTable()->getRelation('Description');
        $this->assertTrue($fk instanceof \Doctrine_Relation_ForeignKey);
        $this->assertEquals($fk->getLocal(), 'file_md5');
        $this->assertEquals($fk->getForeign(), 'file_md5');
        $this->assertTrue($fk->getTable() instanceof \Doctrine_Table);

        $e->Description[0]->description = 'This is the 1st description';
        $e->Description[1]->description = 'This is the 2nd description';
        $this->assertEquals($e->Description[0]->description, 'This is the 1st description');
        $this->assertEquals($e->Description[1]->description, 'This is the 2nd description');
        $this->assertEquals($e->Description[0]->file_md5, $e->file_md5);
        $this->assertEquals($e->Description[1]->file_md5, $e->file_md5);

        $this->assertEquals($e2->Description[0]->description, null);
        $this->assertEquals($e2->Description[1]->description, null);
        $this->assertEquals($e2->Description[0]->file_md5, $e2->file_md5);
        $this->assertEquals($e2->Description[1]->file_md5, $e2->file_md5);

        $e->save();

        $coll = static::$connection->query('FROM TestError');
        $e    = $coll[0];


        $this->assertEquals($e->code, 1);
        $this->assertEquals($e->file_md5, md5(0));
        $this->assertEquals($e->message, 'user error');

        $this->assertTrue($e->Description instanceof \Doctrine_Collection);
        $this->assertTrue($e->Description[0] instanceof \Description);
        $this->assertTrue($e->Description[1] instanceof \Description);

        $this->assertEquals($e->Description[0]->description, 'This is the 1st description');
        $this->assertEquals($e->Description[1]->description, 'This is the 2nd description');

        // UPDATING

        $e->code                        = 2;
        $e->message                     = 'changed message';
        $e->Description[0]->description = '1st changed description';
        $e->Description[1]->description = '2nd changed description';


        $this->assertEquals($e->code, 2);
        $this->assertEquals($e->message, 'changed message');
        $this->assertEquals($e->Description[0]->description, '1st changed description');
        $this->assertEquals($e->Description[1]->description, '2nd changed description');

        $e->save();
        $this->assertEquals($e->code, 2);
        $this->assertEquals($e->message, 'changed message');
        $this->assertEquals($e->Description[0]->description, '1st changed description');
        $this->assertEquals($e->Description[1]->description, '2nd changed description');
    }

    public function testInsert()
    {
        $user       = new \User();
        $user->name = 'John Locke';
        $user->save();

        $this->assertTrue(is_numeric($user->id) && $user->id > 0);

        $this->assertTrue($user->getModified() == []);
        $this->assertTrue($user->state() == \Doctrine_Record_State::CLEAN());

        $user->delete();
        $this->assertEquals($user->state(), \Doctrine_Record_State::TCLEAN());
    }

    public function testUpdate()
    {
        $user = static::$connection->getTable('User')->find(4);
        $user->set('name', 'Jack Daniels', true);


        $user->save();
        //print $this->old->name;

        $this->assertEquals($user->getModified(), []);
        $this->assertEquals($user->name, 'Jack Daniels');
    }

    public function testCopy()
    {
        $user = static::$connection->getTable('User')->find(4);
        $new  = $user->copy();

        $this->assertTrue($new instanceof \Doctrine_Record);
        $this->assertTrue($new->state() == \Doctrine_Record_State::TDIRTY());

        $new = $user->copy();
        $new->save();
        $this->assertEquals($user->name, $new->name);
        $this->assertTrue(is_numeric($new->id) && $new->id > 0);
        $new->refresh();
        $this->assertEquals($user->name, $new->name);
        $this->assertTrue(is_numeric($new->id) && $new->id > 0);
    }

    public function testCopyAndModify()
    {
        $user = static::$connection->getTable('User')->find(4);
        $new  = $user->copy();

        $this->assertTrue($new instanceof \Doctrine_Record);
        $this->assertTrue($new->state() == \Doctrine_Record_State::TDIRTY());

        $new->loginname = 'jackd';

        $this->assertEquals($user->name, $new->name);
        $this->assertEquals($new->loginname, 'jackd');

        $new->save();
        $this->assertTrue(is_numeric($new->id) && $new->id > 0);

        $new->refresh();
        $this->assertEquals($user->name, $new->name);
        $this->assertEquals($new->loginname, 'jackd');
    }

    public function testReferences()
    {
        $user = static::$connection->getTable('User')->find(5);

            $this->assertTrue($user->Phonenumber instanceof \Doctrine_Collection);
            $this->assertEquals($user->Phonenumber->count(), 3);

            $coll = new \Doctrine_Collection('Phonenumber');

            $user->Phonenumber = $coll;
            $this->assertEquals($user->Phonenumber->count(), 0);
            $user->save();

            $user->getTable()->clear();

            $user = static::$connection->getTable('User')->find(5);

            $this->assertEquals($user->Phonenumber->count(), 0);
            $this->assertEquals(get_class($user->Phonenumber), 'Doctrine_Collection');

            $user->Phonenumber[0]->phonenumber;
            $this->assertEquals($user->Phonenumber->count(), 1);

            // ADDING REFERENCES

            $user->Phonenumber[0]->phonenumber = '123 123';

            $this->assertEquals($user->Phonenumber->count(), 1);
            $user->Phonenumber[1]->phonenumber = '123 123';
            $this->assertEquals($user->Phonenumber->count(), 2);

            $user->save();


            $this->assertEquals($user->Phonenumber->count(), 2);

            unset($user);
            $user = static::$connection->getTable('User')->find(5);
            $this->assertEquals($user->Phonenumber->count(), 2);

            $user->Phonenumber[3]->phonenumber = '123 123';
            $user->save();

            $this->assertEquals($user->Phonenumber->count(), 3);
            unset($user);
            $user = static::$connection->getTable('User')->find(5);
            $this->assertEquals($user->Phonenumber->count(), 3);

            // DELETING REFERENCES

            $user->Phonenumber->delete();

            $this->assertEquals($user->Phonenumber->count(), 0);
            unset($user);
            $user = static::$connection->getTable('User')->find(5);
            $this->assertEquals($user->Phonenumber->count(), 0);

            // ADDING REFERENCES WITH STRING KEYS

            $user->Phonenumber['home']->phonenumber = '123 123';
            $user->Phonenumber['work']->phonenumber = '444 444';
            $user->save();

            $this->assertEquals($user->Phonenumber->count(), 2);
            unset($user);
            $user = static::$connection->getTable('User')->find(5);
            $this->assertEquals($user->Phonenumber->count(), 2);

            // REPLACING ONE-TO-MANY REFERENCE
            unset($coll);
            $coll                      = new \Doctrine_Collection('Phonenumber');
            $coll[0]->phonenumber      = '123 123';
            $coll['home']->phonenumber = '444 444';
            $coll['work']->phonenumber = '444 444';

            $user->Phonenumber = $coll;
            $user->save();
            $this->assertEquals($user->Phonenumber->count(), 3);

            $user = static::$connection->getTable('User')->find(5);
            //$this->assertEquals($user->Phonenumber->count(), 3);


            // ONE-TO-ONE REFERENCES

            $user->Email->address = 'drinker@drinkmore.info';
            $this->assertTrue($user->Email instanceof \Email);
            $this->assertEquals($user->Email->address, 'drinker@drinkmore.info');

            $user->save();

            $this->assertTrue($user->Email instanceof \Email);
            $this->assertEquals($user->Email->address, 'drinker@drinkmore.info');
            $this->assertEquals($user->Email->id, $user->email_id);

            $user = static::$connection->getTable('User')->find(5);

            $this->assertTrue($user->Email instanceof \Email);
            $this->assertEquals($user->Email->id, $user->email_id);
            $this->assertEquals($user->Email->state(), \Doctrine_Record_State::CLEAN());
            $this->assertEquals($user->Email->address, 'drinker@drinkmore.info');
            $id = $user->Email->id;

            // REPLACING ONE-TO-ONE REFERENCES

            $email          = static::$connection->create('Email');
            $email->address = 'absolutist@nottodrink.com';
            $user->Email    = $email;

            $this->assertTrue($user->Email instanceof \Email);
            $this->assertEquals($user->Email->address, 'absolutist@nottodrink.com');
            $user->save();
            unset($user);

            $user = static::$connection->getTable('User')->find(5);
            $this->assertTrue($user->Email instanceof \Email);
            $this->assertEquals($user->Email->address, 'absolutist@nottodrink.com');

            $emails = static::$connection->query("FROM Email WHERE Email.id = $id");
            //$this->assertEquals(count($emails),0);
    }

    public function testDeleteReference()
    {
        $user = static::$connection->getTable('User')->find(5);
        $int  = $user->Phonenumber->delete();

        $this->assertTrue($user->Phonenumber->count() == 0);
    }


    public function testSaveAssociations()
    {
        $user = static::$connection->getTable('User')->find(5);

        $gf = static::$connection->getTable('Group');

        $this->assertTrue($user->Group instanceof \Doctrine_Collection);
        $this->assertEquals($user->Group->count(), 1);
        $this->assertEquals($user->Group[0]->id, 3);


        // ADDING ASSOCIATED REFERENCES


        $group1         = $gf->find(1);
        $group2         = $gf->find(2);
        $user->Group[1] = $group1;
        $user->Group[2] = $group2;

        $this->assertEquals($user->Group->count(), 3);

        $user->save();
        $coll = $user->Group;


        // UNSETTING ASSOCIATED REFERENCES
        unset($user);
        $user = static::$connection->getTable('User')->find(5);
        $this->assertEquals($user->Group->count(), 3);
        $this->assertEquals($user->Group[1]->id, 1);
        $this->assertEquals($user->Group[2]->id, 2);

        $user->unlink('Group', [$group1->id, $group2->id], true);
        $this->assertEquals($user->Group->count(), 1);

        $user->save();
        unset($user);


        // CHECKING THE PERSISTENCE OF UNSET ASSOCIATED REFERENCES
        static::$connection->clear();
        $user = static::$connection->getTable('User')->find(5);
        $this->assertEquals($user->Group->count(), 1);
        $this->assertEquals($user->Group[0]->id, 3);
        $this->assertEquals($gf->findAll()->count(), 3);


        // REPLACING OLD ASSOCIATED REFERENCE
        $user->unlink('Group', 3, true);  // you MUST first unlink old relationship
        $user->Group[1] = $group1;
        $user->Group[0] = $group2;
        $user->save();

        $user = static::$connection->getTable('User')->find(5);
        $this->assertEquals($user->Group->count(), 2);
        $this->assertEquals($user->Group[0]->identifier(), $group2->identifier());
        $this->assertEquals($user->Group[1]->identifier(), $group1->identifier());

        $user->unlink('Group', [], true);
        $user->save();
        $user->free();

        $user = static::$connection->getTable('User')->find(5);
        $this->assertEquals($user->Group->count(), 0);


        // ACCESSING ASSOCIATION OBJECT PROPERTIES

        $user = new \User();
        $this->assertTrue($user->getTable()->getRelation('GroupUser') instanceof \Doctrine_Relation_ForeignKey);

        $this->assertTrue($user->GroupUser instanceof \Doctrine_Collection);
        $this->assertTrue($user->GroupUser[0] instanceof \GroupUser);

        $user->name                = 'Jack Daniels';
        $user->Group[0]->name      = 'Group #1';
        $user->Group[1]->name      = 'Group #2';
        $t1                        = time();
        $t2                        = time();
        $user->GroupUser[0]->added = $t1;
        $user->GroupUser[1]->added = $t2;

        $this->assertEquals($user->GroupUser[0]->added, $t1);
        $this->assertEquals($user->GroupUser[1]->added, $t2);

        $user->save();

        $user->refresh();
        $this->assertEquals($user->GroupUser[0]->added, $t1);
        $this->assertEquals($user->GroupUser[1]->added, $t2);
    }


    public function testCount()
    {
        $user = static::$connection->getTable('User')->find(4);

        $this->assertTrue(is_integer($user->count()));
    }

    public function testGetReference()
    {
        $user = static::$connection->getTable('User')->find(4);

        $this->assertTrue($user->Email instanceof \Doctrine_Record);
        $this->assertTrue($user->Phonenumber instanceof \Doctrine_Collection);
        $this->assertTrue($user->Group instanceof \Doctrine_Collection);

        $this->assertTrue($user->Phonenumber->count() == 1);
    }
    public function testGetIterator()
    {
        $user = static::$connection->getTable('User')->find(4);
        $this->assertTrue($user->getIterator() instanceof \ArrayIterator);
    }

    public function testRefreshRelated()
    {
        $user                      = static::$connection->getTable('User')->find(4);
        $user->Address[0]->address = 'Address #1';
        $user->Address[1]->address = 'Address #2';
        $user->save();
        $this->assertEquals(count($user->Address), 2);
        \Doctrine_Query::create()->delete()->from('EntityAddress')->where('user_id = ? AND address_id = ?', [$user->id, $user->Address[1]->id])->execute();
        $user->refreshRelated('Address');
        $this->assertEquals(count($user->Address), 1);
        \Doctrine_Query::create()->delete()->from('EntityAddress')->where('user_id = ? AND address_id = ?', [$user->id, $user->Address[0]->id])->execute();
        $user->refreshRelated();
        $this->assertEquals(count($user->Address), 0);
    }

    public function testRefreshDeep()
    {
        $user                      = static::$connection->getTable('User')->find(4);
        $user->Address[0]->address = 'Address #1';
        $user->Address[1]->address = 'Address #2';
        $user->save();
        $this->assertEquals(count($user->Address), 2);

        \Doctrine_Query::create()->delete()->from('EntityAddress')->where('user_id = ? AND address_id = ?', [$user->id, $user->Address[1]->id])->execute();
        $user->refresh(true);
        $this->assertEquals(count($user->Address), 1);

        $address = $user->Address[0];
        \Doctrine_Query::create()->delete()->from('EntityAddress')->where('user_id = ? AND address_id = ?', [$user->id, $user->Address[0]->id])->execute();
        $user->refresh(true);
        $this->assertEquals(count($user->Address), 0);

        $entity_address             = new \EntityAddress();
        $entity_address->user_id    = $user->id;
        $entity_address->address_id = $address->id;
        $entity_address->save();
        $this->assertNotEquals(count($user->Address), 1);
        $user->refresh(true);
        $this->assertEquals(count($user->Address), 1);
    }

    public function testAggregateWithCommaGroupBy()
    {
        $query = \Doctrine_Query::create()->from('EntityAddress e')->groupby('COALESCE(e.user_id, e.address_id)');
            $this->assertEquals($query->getSqlQuery(), 'SELECT e.user_id AS e__user_id, e.address_id AS e__address_id FROM entity_address e GROUP BY COALESCE(e.user_id, e.address_id)');
    }

    public function testFirstCharUnderscoreInColumnNameAndTableName()
    {
        $record               = new \UnderscoreColumn();
        $record->_underscore_ = 'test';
        $record->save();

        $this->assertEquals($record->_underscore_, 'test');
        $this->assertNotEmpty($record->id);

        $query = new \Doctrine_Query();
        $query->from('UnderscoreColumn');

        $result = $query->execute()->getFirst();
        $this->assertEquals($result->_underscore_, 'test');
    }

    public function testRecordReplaceNoException()
    {
        $user            = new \User();
        $user->name      = 'jon wage';
        $user->loginname = 'jwage';
        $user->replace();
    }

    public function testReplaceReplacesAndNotInsertsNewRecord()
    {
        $users = \Doctrine_Query::create()->from('User u');
        $count = $users->count();

        $user            = new \User();
        $user->name      = 'jon wage2';
        $user->loginname = 'jwage2';
        $user->save();
        $id = $user->id;
        $user->free();
        $count++;

        $users = \Doctrine_Query::create()->from('User u')->execute();
        $this->assertEquals($users->count(), $count);
        $users->free();

        $user = new \User();
        $user->assignIdentifier($id);
        $user->name      = 'jon wage changed';
        $user->loginname = 'jwage2';
        $user->replace();
        $user->free();

        $users = \Doctrine_Query::create()->from('User u')->execute();
        $this->assertEquals($users->count(), $count);
        $users->free();

        $user = \Doctrine_Query::create()->from('User u')->where('u.loginname = ?', 'jwage2')->fetchOne();
        $this->assertEquals($user->name, 'jon wage changed');

        $user->name = 'jon wage changed2';
        $user->replace();

        $user = \Doctrine_Query::create()->from('User u')->where('u.loginname = ?', 'jwage2')->fetchOne();
        $this->assertEquals($user->name, 'jon wage changed2');
    }

    public function testDeleteReturnBooleanAndThrowsException()
    {
        $user            = new \User();
        $user->name      = 'jonnnn wage';
        $user->loginname = 'jwage3';
        $user->save();

        $this->assertTrue($user->delete());
        // delete() on transient objects should just be ignored.
        $user->delete();
    }
}
