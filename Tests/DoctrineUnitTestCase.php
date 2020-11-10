<?php
namespace Tests;

use Doctrine_Core;
use Doctrine_Manager;
use Doctrine_Manager_Exception;
use Doctrine_Query;
use Doctrine_Adapter_Mock;
use Doctrine_EventListener;
use Doctrine_Connection;
use Doctrine_Connection_Exception;
use Doctrine_Collection;
use PDO;

use PHPUnit\Framework\TestCase;

class DoctrineUnitTestCase extends TestCase
{
    protected static $connection;
    protected static $objTable;
    protected static $dbh;
    protected static $listener;
    protected static $unitOfWork;
    protected static $driverName = false;
    protected static Doctrine_Connection $conn;
    protected static $adapter;
    protected static array $tables = [];
    protected static Doctrine_Manager $manager;

    public static function setUpBeforeClass(): void
    {
        static::$manager = \Doctrine_Manager::getInstance();
        static::$manager->setAttribute(\Doctrine_Core::ATTR_EXPORT, \Doctrine_Core::EXPORT_ALL);

        static::$tables = array_merge(
            static::$tables,
            [
                'Entity',
                'EntityReference',
                'Email',
                'Phonenumber',
                'GroupUser',
                'Album',
                'Song',
                'Element',
                'TestError',
                'Description',
                'Address',
                'Account',
                'Task',
                'Resource',
                'Assignment',
                'ResourceType',
                'ResourceReference',
            ]
        );


        $e = explode('_', static::class);
        if (! static::$driverName) {
            static::$driverName = 'main';

            switch ($e[1]) {
                case 'Export':
                case 'Import':
                case 'Transaction':
                case 'DataDict':
                case 'Sequence':
                    static::$driverName = 'Sqlite';
                    break;
            }

            $module = $e[1];

            if (count($e) > 3) {
                $driver = $e[2];
                switch ($e[2]) {
                    case 'Mysql':
                    case 'Pgsql':
                    case 'Sqlite':
                        static::$driverName = $e[2];
                        break;
                }
            }
        }

        try {
            static::$conn = static::$connection = static::$manager->getConnection(static::$driverName);
            static::$manager->setCurrentConnection(static::$driverName);

            static::$connection->evictTables();
            static::$dbh      = static::$adapter      = static::$connection->getDbh();
            static::$listener = static::$manager->getAttribute(\Doctrine_Core::ATTR_LISTENER);

            static::$manager->setAttribute(\Doctrine_Core::ATTR_LISTENER, static::$listener);
        } catch (Doctrine_Manager_Exception $e) {
            if (static::$driverName == 'main') {
                static::$dbh = new \PDO('sqlite::memory:');
                static::$dbh->sqliteCreateFunction('trim', 'trim', 1);
            } else {
                static::$dbh = static::$adapter = new \Doctrine_Adapter_Mock(static::$driverName);
            }

            static::$conn = static::$connection = static::$manager->openConnection(static::$dbh, static::$driverName);

            static::$listener = new \Doctrine_EventListener();
            static::$manager->setAttribute(\Doctrine_Core::ATTR_LISTENER, static::$listener);
        }
        static::$unitOfWork = static::$connection->unitOfWork;
        static::$connection->setListener(new \Doctrine_EventListener());

        if (static::$driverName === 'main') {
            static::prepareTables();
            static::prepareData();
            foreach (static::$tables as $name) {
                static::$connection->getTable(ucwords($name))->clear();
            }
        }

        static::$conn->setAttribute(\Doctrine_Core::ATTR_USE_NATIVE_SET, false);
        static::$conn->setAttribute(\Doctrine_Core::ATTR_USE_NATIVE_ENUM, false);
    }

    public function tearDown(): void
    {
        if (isset(static::$objTable)) {
            static::$objTable->clear();
        }
    }

    public static function prepareTables(): void
    {
        foreach (static::$tables as $name) {
            $name  = ucwords($name);
            $table = static::$connection->getTable($name);
            $query = 'DROP TABLE ' . $table->getTableName();
            try {
                static::$conn->exec($query);
            } catch (Doctrine_Connection_Exception $e) {
            }
        }
        static::$conn->export->exportClasses(static::$tables);
        static::$objTable = static::$connection->getTable('User');
    }

    public static function prepareData(): void
    {
        $groups = new \Doctrine_Collection(static::$connection->getTable('Group'));

        $groups[0]->name = 'Drama Actors';

        $groups[1]->name = 'Quality Actors';


        $groups[2]->name                          = 'Action Actors';
        $groups[2]['Phonenumber'][0]->phonenumber = '123 123';
        $groups->save();

        $users = new \Doctrine_Collection('User');


        $users[0]->name                          = 'zYne';
        $users[0]['Email']->address              = 'zYne@example.com';
        $users[0]['Phonenumber'][0]->phonenumber = '123 123';

        $users[1]->name                          = 'Arnold Schwarzenegger';
        $users[1]->Email->address                = 'arnold@example.com';
        $users[1]['Phonenumber'][0]->phonenumber = '123 123';
        $users[1]['Phonenumber'][1]->phonenumber = '456 456';
        $users[1]->Phonenumber[2]->phonenumber   = '789 789';
        $users[1]->Group[0]                      = $groups[2];

        $users[2]->name                        = 'Michael Caine';
        $users[2]->Email->address              = 'caine@example.com';
        $users[2]->Phonenumber[0]->phonenumber = '123 123';

        $users[3]->name                        = 'Takeshi Kitano';
        $users[3]->Email->address              = 'kitano@example.com';
        $users[3]->Phonenumber[0]->phonenumber = '111 222 333';

        $users[4]->name                          = 'Sylvester Stallone';
        $users[4]->Email->address                = 'stallone@example.com';
        $users[4]->Phonenumber[0]->phonenumber   = '111 555 333';
        $users[4]['Phonenumber'][1]->phonenumber = '123 213';
        $users[4]['Phonenumber'][2]->phonenumber = '444 555';

        $users[5]->name                        = 'Kurt Russell';
        $users[5]->Email->address              = 'russell@example.com';
        $users[5]->Phonenumber[0]->phonenumber = '111 222 333';

        $users[6]->name                          = 'Jean Reno';
        $users[6]->Email->address                = 'reno@example.com';
        $users[6]->Phonenumber[0]->phonenumber   = '111 222 333';
        $users[6]['Phonenumber'][1]->phonenumber = '222 123';
        $users[6]['Phonenumber'][2]->phonenumber = '123 456';

        $users[7]->name                        = 'Edward Furlong';
        $users[7]->Email->address              = 'furlong@example.com';
        $users[7]->Phonenumber[0]->phonenumber = '111 567 333';

        $users->save();
    }

    public function getConnection()
    {
        return static::$connection;
    }

    public function assertDeclarationType($type, $type2)
    {
        $dec = $this->getDeclaration($type);

        if (!is_array($type2)) {
            $type2 = [$type2];
        }

        $this->assertEquals($dec['type'], $type2);
    }

    public function getDeclaration($type)
    {
        return static::$connection->dataDict->getPortableDeclaration(['type' => $type, 'name' => 'colname', 'length' => 1, 'fixed' => true]);
    }
}
