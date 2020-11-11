<?php
namespace Tests\Connection {
    use PHPUnit\Framework\TestCase;

    class CustomTest extends TestCase
    {
        public function testConnection()
        {
            $manager = \Doctrine_Manager::getInstance();
            $manager->registerConnectionDriver('test', \Doctrine_Connection_Test::class);
            $conn = $manager->openConnection('test://username:password@localhost/dbname', false);
            $dbh  = $conn->getDbh();

            $this->assertInstanceOf(\Doctrine_Connection_Test::class, $conn);
            $this->assertInstanceOf(\Doctrine_Adapter_Test::class, $dbh);
        }
    }
}

namespace {
    class Doctrine_Connection_Test extends Doctrine_Connection_Common
    {
    }

    class Doctrine_Adapter_Test implements Doctrine_Adapter_Interface
    {
        public function __construct($dsn, $username, $password, $options)
        {
        }

        public function prepare($prepareString)
        {
        }

        public function query($queryString)
        {
        }

        public function quote($input)
        {
        }

        public function exec($statement)
        {
        }

        public function lastInsertId()
        {
        }

        public function beginTransaction()
        {
        }

        public function commit()
        {
        }

        public function rollBack()
        {
        }

        public function errorCode()
        {
        }

        public function errorInfo()
        {
        }

        public function getAttribute($attribute)
        {
        }

        public function setAttribute($attribute, $value)
        {
        }

        public function sqliteCreateFunction()
        {
        }
    }
}
