<?php
/* THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

/**
 * Doctrine_Data_Import_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Data_Import_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables[] = 'User';
        $this->tables[] = 'Phonenumber';
        $this->tables[] = 'Album';
        parent::prepareTables();
    }

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
        try {
            file_put_contents('test.yml', $yml);
            Doctrine_Core::loadData('test.yml', true);

            $this->conn->clear();

            $query = new Doctrine_Query();
            $query->from('User u, u.Phonenumber')
                  ->where('u.name = ?', 'jwage');

            $user = $query->execute()->getFirst();

            $this->assertEqual($user->name, 'jwage');
            $this->assertEqual($user->Phonenumber->count(), 1);
            $this->assertEqual($user->Phonenumber[0]->phonenumber, '6155139185');

            $this->pass();
        } catch (Exception $e) {
            $this->fail();
        }

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
        try {
            file_put_contents('test.yml', $yml);
            Doctrine_Core::loadData('test.yml', true);

            $this->conn->clear();

            $query = new Doctrine_Query();
            $query->from('User u, u.Album a, a.User u2')
                  ->where('u.name = ?', 'zYne-');

            $user = $query->execute()->getFirst();

            $this->assertEqual($user->name, 'zYne-');
            $this->assertEqual($user->Album->count(), 1);
            $this->assertEqual($user->Album[0]->name, 'zYne- Christmas Album');

            $this->pass();
        } catch (Exception $e) {
            $this->fail();
        }
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
        try {
            file_put_contents('test.yml', $yml);
            Doctrine_Core::loadData('test.yml', true);

            $this->conn->clear();

            $query = Doctrine_Query::create();
            $query->from('User u, u.Phonenumber')
                  ->where('u.name = ?', 'jwage2');

            $user = $query->execute()->getFirst();

            $this->assertEqual($user->name, 'jwage2');
            $this->assertEqual($user->Phonenumber->count(), 2);
            $this->assertEqual($user->Phonenumber[0]->phonenumber, '6155139185');
            $this->assertEqual($user->Phonenumber[1]->phonenumber, '6153137679');

            $this->pass();
        } catch (Exception $e) {
            $this->fail();
        }
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

Groupuser:
  Groupuser_1:
    User: User_1
    Group: Group_1

Group:
  Group_1:
    name: test
END;
        try {
            file_put_contents('test.yml', $yml);
            Doctrine_Core::loadData('test.yml', true);

            $this->conn->clear();

            $testRef = Doctrine_Query::create()->from('Groupuser')->execute()->getFirst();

            $this->assertTrue($testRef->group_id > 0);
            $this->assertTrue($testRef->user_id > 0);

            $this->pass();
        } catch (Exception $e) {
            $this->fail($e->getMessage());
        }

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

Groupuser:
  Groupuser_1:
    User: User_1
    Group: Group_1

Group:
  Group_1:
    name: test
END;
        try {
            file_put_contents('test.yml', $yml);
            Doctrine_Core::loadData('test.yml', true);
            $this->fail();
        } catch (Exception $e) {
            $this->pass();
        }

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
        $import = new Doctrine_Data_Import(getcwd() . '/test_data_fixtures');
        $import->setFormat('yml');

        $array = $import->doParsing();

        // Last User definition in test2.yml takes presedence
        $this->assertTrue(isset($array['User']['User_2']));

        unlink('test_data_fixtures/test1.yml');
        unlink('test_data_fixtures/test2.yml');
        rmdir('test_data_fixtures');
    }

    public function testRecursiveFixturesLoading()
    {
        Doctrine_Manager::getInstance()->setAttribute(Doctrine_Core::ATTR_RECURSIVE_MERGE_FIXTURES, true);
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
        $import = new Doctrine_Data_Import(getcwd() . '/test_data_fixtures');
        $import->setFormat('yml');

        $array = $import->doParsing();

        $this->assertTrue(isset($array['User']['User_1']));
        $this->assertTrue(isset($array['User']['User_2']));

        unlink('test_data_fixtures/test1.yml');
        unlink('test_data_fixtures/test2.yml');
        rmdir('test_data_fixtures');
        Doctrine_Manager::getInstance()->setAttribute(Doctrine_Core::ATTR_RECURSIVE_MERGE_FIXTURES, false);
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

Groupuser:
  Groupuser_1:
    User: Group_1
    Group: User_1

Group:
  Group_1:
    name: test
END;
        try {
            file_put_contents('test.yml', $yml);
            Doctrine_Core::loadData('test.yml', true);

            $this->conn->clear();

            $testRef = Doctrine_Query::create()->from('Groupuser')->execute()->getFirst();

            $this->assertTrue($testRef->group_id > 0);
            $this->assertTrue($testRef->user_id > 0);

            $this->fail();
        } catch (Exception $e) {
            $this->pass();
            $this->assertEqual($e->getMessage(), 'Class referred to in "(groupuser) Groupuser_1" is expected to be "User" and "Group" was given');
        }

        unlink('test.yml');
    }
}
