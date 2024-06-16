<?php

namespace Tests;

use Doctrine1\Core;
use Doctrine1\Manager;
use Doctrine1\Manager\Exception;
use Doctrine1\Query;
use Doctrine1\Adapter\Mock;
use Doctrine1\EventListener;
use Doctrine1\Connection;
use Doctrine1\Collection;
use PDO;
use Spatie\Snapshots\MatchesSnapshots;

use PHPUnit\Framework\TestCase;

class DoctrineUnitTestCase extends TestCase
{
    use MatchesSnapshots;

    protected static Connection $connection;
    protected static ?PDO $dbh = null;
    protected static ?\Doctrine1\EventListener $listener;
    protected static $unitOfWork;
    protected static ?string $driverName = null;
    protected static ?\Doctrine1\Connection $conn = null;
    protected static $adapter;
    protected static array $tables = [];
    protected static Manager $manager;

    protected function getSnapshotDirectory(): string
    {
        return __DIR__ . "/snapshots";
    }

    public static function setUpBeforeClass(): void
    {
        static::$manager = Manager::getInstance();
        static::$manager->setExportFlags(Core::EXPORT_ALL);

        static::$tables = array_merge(static::$tables, [
            "Entity",
            "EntityReference",
            "Email",
            "Phonenumber",
            "GroupUser",
            "Album",
            "Song",
            "Element",
            "TestError",
            "Description",
            "Address",
            "Account",
            "Task",
            "Resource",
            "Assignment",
            "ResourceType",
            "ResourceReference",
        ]);

        if (!static::$driverName) {
            static::$driverName = "main";
        }

        try {
            static::$conn = static::$connection = static::$manager->getConnection(static::$driverName);
            static::$manager->setCurrentConnection(static::$driverName);

            static::$connection->evictTables();
            static::$dbh = static::$adapter = static::$connection->getDbh();
            static::$listener = static::$manager->getListener();

            static::$manager->setListener(static::$listener);
        } catch (Manager\Exception $e) {
            if (static::$driverName == "main") {
                static::$dbh = new \PDO("sqlite::memory:");
                static::$dbh->sqliteCreateFunction("trim", "trim", 1);
            } else {
                static::$dbh = static::$adapter = new \Doctrine1\Adapter\Mock(static::$driverName);
            }

            static::$conn = static::$connection = static::$manager->openConnection(static::$dbh, static::$driverName);

            static::$listener = new \Doctrine1\EventListener();
            static::$manager->setListener(static::$listener);
        }
        static::$unitOfWork = static::$connection->unitOfWork;
        static::$connection->setListener(new \Doctrine1\EventListener());

        if (static::$driverName === "main") {
            static::prepareTables();
            static::prepareData();
        }

        static::$conn->setUseNativeSet(false);
        static::$conn->setUseNativeEnum(false);
    }

    public static function tearDownAfterClass(): void
    {
        static::$manager->resetInstance();
    }

    public function setUp(): void
    {
        foreach (static::$tables as $name) {
            static::$connection->getTable(ucwords($name))->clear();
        }
    }

    public static function prepareTables(): void
    {
        foreach (static::$tables as $name) {
            $name = ucwords($name);
            $table = static::$connection->getTable($name);
            $query = "DROP TABLE " . $table->getTableName();
            try {
                static::$conn->exec($query);
            } catch (Connection\Exception $e) {
            }
        }
        static::$conn->export->exportClasses(static::$tables);
    }

    public static function prepareData(): void
    {
        $groups = new \Doctrine1\Collection(static::$connection->getTable("Group"));

        $groups[0]->name = "Drama Actors";

        $groups[1]->name = "Quality Actors";

        $groups[2]->name = "Action Actors";
        $groups[2]["Phonenumber"][0]->phonenumber = "123 123";
        $groups->save();

        $users = new \Doctrine1\Collection("User");

        $users[0]->name = "zYne";
        $users[0]["Email"] = new \Email();
        $users[0]["Email"]->address = "zYne@example.com";
        $users[0]["Phonenumber"][0]->phonenumber = "123 123";

        $users[1]->name = "Arnold Schwarzenegger";
        $users[1]->Email = new \Email();
        $users[1]->Email->address = "arnold@example.com";
        $users[1]["Phonenumber"][0]->phonenumber = "123 123";
        $users[1]["Phonenumber"][1]->phonenumber = "456 456";
        $users[1]->Phonenumber[2]->phonenumber = "789 789";
        $users[1]->Group[0] = $groups[2];

        $users[2]->name = "Michael Caine";
        $users[2]->Email = new \Email();
        $users[2]->Email->address = "caine@example.com";
        $users[2]->Phonenumber[0]->phonenumber = "123 123";

        $users[3]->name = "Takeshi Kitano";
        $users[3]->Email = new \Email();
        $users[3]->Email->address = "kitano@example.com";
        $users[3]->Phonenumber[0]->phonenumber = "111 222 333";

        $users[4]->name = "Sylvester Stallone";
        $users[4]->Email = new \Email();
        $users[4]->Email->address = "stallone@example.com";
        $users[4]->Phonenumber[0]->phonenumber = "111 555 333";
        $users[4]["Phonenumber"][1]->phonenumber = "123 213";
        $users[4]["Phonenumber"][2]->phonenumber = "444 555";

        $users[5]->name = "Kurt Russell";
        $users[5]->Email = new \Email();
        $users[5]->Email->address = "russell@example.com";
        $users[5]->Phonenumber[0]->phonenumber = "111 222 333";

        $users[6]->name = "Jean Reno";
        $users[6]->Email = new \Email();
        $users[6]->Email->address = "reno@example.com";
        $users[6]->Phonenumber[0]->phonenumber = "111 222 333";
        $users[6]["Phonenumber"][1]->phonenumber = "222 123";
        $users[6]["Phonenumber"][2]->phonenumber = "123 456";

        $users[7]->name = "Edward Furlong";
        $users[7]->Email = new \Email();
        $users[7]->Email->address = "furlong@example.com";
        $users[7]->Phonenumber[0]->phonenumber = "111 567 333";

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

        $this->assertEquals($dec["type"], $type2);
    }

    public function getDeclaration($type)
    {
        return static::$connection->dataDict->getPortableDeclaration(["type" => $type, "name" => "colname", "length" => 1, "fixed" => true]);
    }
}
