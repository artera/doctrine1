<?php
namespace Tests\Core\Hydrate;

use Tests\DoctrineUnitTestCase;

class ScalarTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
        $user                              = new \User();
        $user->name                        = 'romanb';
        $user->Phonenumber[0]->phonenumber = '112';
        $user->Phonenumber[1]->phonenumber = '110';
        $user->save();
    }

    protected static array $tables = ['Entity', 'Phonenumber'];

    public function testHydrateScalarWithJoin()
    {
        $q = \Doctrine1\Query::create();
        $q->select('u.*, p.*')
            ->from('User u')
            ->innerJoin('u.Phonenumber p');

        $res = $q->execute([], \Doctrine1\Core::HYDRATE_SCALAR);

        $this->assertTrue(is_array($res));
        $this->assertEquals(2, count($res));
        //row1
        $this->assertEquals(1, $res[0]['u_id']);
        $this->assertEquals('romanb', $res[0]['u_name']);
        $this->assertEquals(null, $res[0]['u_loginname']);
        $this->assertEquals(null, $res[0]['u_password']);
        $this->assertEquals(0, $res[0]['u_type']);
        $this->assertEquals(null, $res[0]['u_created']);
        $this->assertEquals(null, $res[0]['u_updated']);
        $this->assertEquals(null, $res[0]['u_email_id']);
        $this->assertEquals(1, $res[0]['p_id']);
        $this->assertEquals(112, $res[0]['p_phonenumber']);
        $this->assertEquals(1, $res[0]['p_entity_id']);
        //row2
        $this->assertEquals(1, $res[1]['u_id']);
        $this->assertEquals('romanb', $res[1]['u_name']);
        $this->assertEquals(null, $res[1]['u_loginname']);
        $this->assertEquals(null, $res[1]['u_password']);
        $this->assertEquals(0, $res[1]['u_type']);
        $this->assertEquals(null, $res[1]['u_created']);
        $this->assertEquals(null, $res[1]['u_updated']);
        $this->assertEquals(null, $res[1]['u_email_id']);
        $this->assertEquals(2, $res[1]['p_id']);
        $this->assertEquals(110, $res[1]['p_phonenumber']);
        $this->assertEquals(1, $res[1]['p_entity_id']);

        $q->free();
    }

    public function testHydrateScalar()
    {
        $q = \Doctrine1\Query::create();
        $q->select('u.*')->from('User u');

        $res = $q->execute([], \Doctrine1\Core::HYDRATE_SCALAR);

        $this->assertTrue(is_array($res));
        $this->assertEquals(1, count($res));
        //row1
        $this->assertEquals(1, $res[0]['u_id']);
        $this->assertEquals('romanb', $res[0]['u_name']);
        $this->assertEquals(null, $res[0]['u_loginname']);
        $this->assertEquals(null, $res[0]['u_password']);
        $this->assertEquals(0, $res[0]['u_type']);
        $this->assertEquals(null, $res[0]['u_created']);
        $this->assertEquals(null, $res[0]['u_updated']);
        $this->assertEquals(null, $res[0]['u_email_id']);

        $q->free();
    }

    public function testHydrateSingleScalarDoesNotAddPKToSelect()
    {
        $q = \Doctrine1\Query::create();
        $q->select('u.name')->from('User u');
        $res = $q->execute([], \Doctrine1\Core::HYDRATE_SINGLE_SCALAR);
        $this->assertEquals('romanb', $res);
        $q->free();
    }

    public function testHydrateSingleScalarWithAggregate()
    {
        $q = \Doctrine1\Query::create();
        $q->select('COUNT(u.id) num_ids')->from('User u');
        $res = $q->execute([], \Doctrine1\Core::HYDRATE_SINGLE_SCALAR);
        $this->assertEquals(1, $res);
        $q->free();
    }

    public function testHydrateScalarWithJoinAndAggregate()
    {
        $q = \Doctrine1\Query::create();
        $q->select('u.id, UPPER(u.name) nameUpper, p.*')
            ->from('User u')
            ->innerJoin('u.Phonenumber p');

        $res = $q->execute([], \Doctrine1\Core::HYDRATE_SCALAR);

        $this->assertTrue(is_array($res));
        $this->assertEquals(2, count($res));

        //row1
        $this->assertEquals(1, $res[0]['u_id']);
        $this->assertEquals('ROMANB', $res[0]['u_nameUpper']);
        $this->assertEquals(1, $res[0]['p_id']);
        $this->assertEquals(112, $res[0]['p_phonenumber']);
        $this->assertEquals(1, $res[0]['p_entity_id']);
        //row2
        $this->assertEquals(1, $res[1]['u_id']);
        $this->assertEquals('ROMANB', $res[1]['u_nameUpper']);
        $this->assertEquals(2, $res[1]['p_id']);
        $this->assertEquals(110, $res[1]['p_phonenumber']);
        $this->assertEquals(1, $res[1]['p_entity_id']);

        $q->free();
    }

    public function testHydrateArrayShallowWithJoin()
    {
        $q = \Doctrine1\Query::create();
        $q->select('u.*, p.id as phonenumber_id, p.phonenumber, p.entity_id')
            ->from('User u')
            ->innerJoin('u.Phonenumber p');

        $res = $q->execute([], \Doctrine1\Core::HYDRATE_ARRAY_SHALLOW);

        $this->assertTrue(is_array($res));
        $this->assertEquals(2, count($res));
        //row1
        $this->assertEquals(1, $res[0]['id']);
        $this->assertEquals('romanb', $res[0]['name']);
        $this->assertEquals(null, $res[0]['loginname']);
        $this->assertEquals(null, $res[0]['password']);
        $this->assertEquals(0, $res[0]['type']);
        $this->assertEquals(null, $res[0]['created']);
        $this->assertEquals(null, $res[0]['updated']);
        $this->assertEquals(null, $res[0]['email_id']);
        $this->assertEquals(1, $res[0]['phonenumber_id']);
        $this->assertEquals(112, $res[0]['phonenumber']);
        $this->assertEquals(1, $res[0]['entity_id']);
        //row2
        $this->assertEquals(1, $res[1]['id']);
        $this->assertEquals('romanb', $res[1]['name']);
        $this->assertEquals(null, $res[1]['loginname']);
        $this->assertEquals(null, $res[1]['password']);
        $this->assertEquals(0, $res[1]['type']);
        $this->assertEquals(null, $res[1]['created']);
        $this->assertEquals(null, $res[1]['updated']);
        $this->assertEquals(null, $res[1]['email_id']);
        $this->assertEquals(2, $res[1]['phonenumber_id']);
        $this->assertEquals(110, $res[1]['phonenumber']);
        $this->assertEquals(1, $res[1]['entity_id']);

        $q->free();
    }

    public function testHydrateArrayShallow()
    {
        $q = \Doctrine1\Query::create();
        $q->select('u.*')->from('User u');

        $res = $q->execute([], \Doctrine1\Core::HYDRATE_ARRAY_SHALLOW);

        $this->assertTrue(is_array($res));
        $this->assertEquals(1, count($res));
        //row1
        $this->assertEquals(1, $res[0]['id']);
        $this->assertEquals('romanb', $res[0]['name']);
        $this->assertEquals(null, $res[0]['loginname']);
        $this->assertEquals(null, $res[0]['password']);
        $this->assertEquals(0, $res[0]['type']);
        $this->assertEquals(null, $res[0]['created']);
        $this->assertEquals(null, $res[0]['updated']);
        $this->assertEquals(null, $res[0]['email_id']);

        $q->free();
    }

    public function testHydrateArrayShallowWithJoinAndAggregate()
    {
        $q = \Doctrine1\Query::create();
        $q->select('u.id, UPPER(u.name) nameUpper, p.id as phonenumber_id, p.phonenumber, p.entity_id')
            ->from('User u')
            ->innerJoin('u.Phonenumber p');

        $res = $q->execute([], \Doctrine1\Core::HYDRATE_ARRAY_SHALLOW);

        $this->assertTrue(is_array($res));
        $this->assertEquals(2, count($res));

        //row1
        $this->assertEquals(1, $res[0]['id']);
        $this->assertEquals('ROMANB', $res[0]['nameUpper']);
        $this->assertEquals(1, $res[0]['id']);
        $this->assertEquals(112, $res[0]['phonenumber']);
        $this->assertEquals(1, $res[0]['entity_id']);
        //row2
        $this->assertEquals(1, $res[1]['id']);
        $this->assertEquals('ROMANB', $res[1]['nameUpper']);
        $this->assertEquals(2, $res[1]['phonenumber_id']);
        $this->assertEquals(110, $res[1]['phonenumber']);
        $this->assertEquals(1, $res[1]['entity_id']);

        $q->free();
    }
}
