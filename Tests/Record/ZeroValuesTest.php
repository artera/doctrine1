<?php
namespace Tests\Record;

use Tests\DoctrineUnitTestCase;

class ZeroValuesTest extends DoctrineUnitTestCase
{
    public static function prepareTables(): void
    {
        static::$tables[] = 'ZeroValueTest';

        parent::prepareTables();
    }

    public static function prepareData(): void
    {
        $user                   = new \ZeroValueTest();
        $user['is_super_admin'] = 0; // set it to 0 and it should be 0 when we pull it back from the database
        $user['username']       = 'jwage';
        $user['salt']           = 'test';
        $user['password']       = 'test';
        $user->save();
    }

    public function testZeroValuesMaintained()
    {
        $users = static::$dbh->query('SELECT * FROM zero_value_test')->fetchAll(\PDO::FETCH_ASSOC);

        $this->assertSame($users[0]['is_super_admin'], '0');
    }

    public function testZeroValuesMaintained2()
    {
        $q = new \Doctrine_Query();
        $q->from('ZeroValueTest');
        $users = $q->execute([], \Doctrine_Core::HYDRATE_ARRAY);

        $this->assertSame($users[0]['is_super_admin'], false);
        // check for aggregate bug
        $this->assertTrue(!isset($users[0][0]));
    }

    public function testZeroValuesMaintained3()
    {
        $q = new \Doctrine_Query();
        $q->from('ZeroValueTest');
        $users = $q->execute();

        $this->assertSame($users[0]['is_super_admin'], false);
    }
}
