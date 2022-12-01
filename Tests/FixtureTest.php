<?php

namespace Tests;

class FixtureTest extends DoctrineUnitTestCase
{
    protected static array $tables = ['User', 'Phonenumber', 'Album'];

    public function testInlineMany()
    {
        $yml = <<<END
---
User:
  User_1:
    name: jwage
    password: changeme
    Phonenumber:
      Phonenumber_1:
        phonenumber: 6155139185
END;
        file_put_contents('test.yml', $yml);
        \Doctrine1\Core::loadData('test.yml', true);

        static::$conn->clear();

        $query = new \Doctrine1\Query();
        $query->from('User u, u.Phonenumber')
            ->where('u.name = ?', 'jwage');

        $user = $query->execute()->getFirst();

        $this->assertEquals($user->name, 'jwage');
        $this->assertEquals($user->Phonenumber->count(), 1);
        $this->assertEquals($user->Phonenumber[0]->phonenumber, '6155139185');
        unlink('test.yml');
    }

    public function testInlineOne()
    {
        $yml = <<<END
---
Album:
  Album_1:
    name: zYne- Christmas Album
    User:
      name: zYne-
      password: changeme
END;
        file_put_contents('test.yml', $yml);
        \Doctrine1\Core::loadData('test.yml', true);

        static::$conn->clear();

        $query = new \Doctrine1\Query();
        $query->from('User u, u.Album a, a.User u2')
            ->where('u.name = ?', 'zYne-');

        $user = $query->execute()->getFirst();

        $this->assertEquals($user->name, 'zYne-');
        $this->assertEquals($user->Album->count(), 1);
        $this->assertEquals($user->Album[0]->name, 'zYne- Christmas Album');
        unlink('test.yml');
    }

    public function testNormalMany()
    {
        $yml = <<<END
---
User:
  User_1:
    name: jwage2
    password: changeme
    Phonenumber: [Phonenumber_1, Phonenumber_2]
Phonenumber:
  Phonenumber_1:
    phonenumber: 6155139185
  Phonenumber_2:
    phonenumber: 6153137679
END;
        file_put_contents('test.yml', $yml);
        \Doctrine1\Core::loadData('test.yml', true);

        static::$conn->clear();

        $query = \Doctrine1\Query::create();
        $query->from('User u, u.Phonenumber')
            ->where('u.name = ?', 'jwage2');

        $user = $query->execute()->getFirst();

        $this->assertEquals($user->name, 'jwage2');
        $this->assertEquals($user->Phonenumber->count(), 2);
        $this->assertEquals($user->Phonenumber[0]->phonenumber, '6155139185');
        $this->assertEquals($user->Phonenumber[1]->phonenumber, '6153137679');
        unlink('test.yml');
    }

    public function testMany2ManyManualDataFixtures()
    {
        self::prepareTables();
        $yml = <<<END
---
User:
  User_1:
    name: jwage400
    password: changeme

GroupUser:
  GroupUser_1:
    User: User_1
    Group: Group_1

Group:
  Group_1:
    name: test
END;
        file_put_contents('test.yml', $yml);
        \Doctrine1\Core::loadData('test.yml', true);

        static::$conn->clear();

        $testRef = \Doctrine1\Query::create()->from('GroupUser')->execute()->getFirst();

        $this->assertTrue($testRef->group_id > 0);
        $this->assertTrue($testRef->user_id > 0);
        unlink('test.yml');
    }

    public function testInvalidElementThrowsException()
    {
        self::prepareTables();
        $yml = <<<END
---
User:
  User_1:
    name: jwage400
    pass: changeme

GroupUser:
  GroupUser_1:
    User: User_1
    Group: Group_1

Group:
  Group_1:
    name: test
END;
        $this->expectException(\Doctrine1\Record\UnknownPropertyException::class);
        file_put_contents('test.yml', $yml);
        \Doctrine1\Core::loadData('test.yml', true);
        unlink('test.yml');
    }

    public function testNormalNonRecursiveFixturesLoading()
    {
        self::prepareTables();
        $yml1 = <<<END
---
User:
  User_1:
    name: jwage400
    pass: changeme
END;

        $yml2 = <<<END
---
User:
  User_2:
    name: jwage500
    pass: changeme2
END;

        mkdir('test_data_fixtures');
        file_put_contents('test_data_fixtures/test1.yml', $yml1);
        file_put_contents('test_data_fixtures/test2.yml', $yml2);
        $import = new \Doctrine1\Data\Import(getcwd() . '/test_data_fixtures');
        $import->setFormat('yml');

        $array = $import->doParsing();

        // Last User definition in test2.yml takes presedence
        $this->assertTrue(isset($array['User']['User_2']));

        unlink('test_data_fixtures/test1.yml');
        unlink('test_data_fixtures/test2.yml');
        rmdir('test_data_fixtures');
    }

    public function testIncorrectYamlRelationThrowsException()
    {
        self::prepareTables();
        $yml = <<<END
---
User:
  User_1:
    name: jwage400
    password: changeme

GroupUser:
  GroupUser_1:
    User: Group_1
    Group: User_1

Group:
  Group_1:
    name: test
END;
        $this->expectException(\Doctrine1\Data\Exception::class);
        file_put_contents('test.yml', $yml);
        \Doctrine1\Core::loadData('test.yml', true);

        static::$conn->clear();

        $testRef = \Doctrine1\Query::create()->from('GroupUser')->execute()->getFirst();

        $this->assertTrue($testRef->group_id > 0);
        $this->assertTrue($testRef->user_id > 0);
        unlink('test.yml');
    }
}
