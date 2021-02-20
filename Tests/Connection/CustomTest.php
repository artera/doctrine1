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

    class Doctrine_Adapter_Test extends PDO
    {
        public function __construct($dsn, $username, $password, $options)
        {
        }

        public function prepare(string $query, array $options = [])
        {
        }

        public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs)
        {
        }

        public function quote(string $string, int $type = PDO::PARAM_STR)
        {
            return '';
        }

        public function exec(string $statement)
        {
        }

        public function lastInsertId(?string $name = null): string
        {
            return '1';
        }

        public function beginTransaction(): bool
        {
            return true;
        }

        public function commit(): bool
        {
            return true;
        }

        public function rollBack(): bool
        {
            return true;
        }

        public function errorCode(): int
        {
            return 0;
        }

        public function errorInfo(): string
        {
            return '';
        }

        public function getAttribute(int $attribute): mixed
        {
            return true;
        }

        public function setAttribute(int $attribute, mixed $value): bool
        {
            return true;
        }

        public function sqliteCreateFunction()
        {
        }
    }
}
