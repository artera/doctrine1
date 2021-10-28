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
    class Doctrine_Connection_Test extends Doctrine_Connection
    {
    }

    class Doctrine_Adapter_Test extends PDO
    {
        public function __construct($dsn, $username, $password, $options)
        {
        }

        #[\ReturnTypeWillChange]
        public function prepare(string $query, array $options = [])
        {
            return false;
        }

        #[\ReturnTypeWillChange]
        public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs)
        {
            return false;
        }

        #[\ReturnTypeWillChange]
        public function quote(string $string, int $type = PDO::PARAM_STR)
        {
            return '';
        }

        #[\ReturnTypeWillChange]
        public function exec(string $statement)
        {
            return false;
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

        public function errorCode(): ?string
        {
            return null;
        }

        public function errorInfo(): array
        {
            return [];
        }

        public function getAttribute(int $attribute): mixed
        {
            return true;
        }

        public function setAttribute(int $attribute, mixed $value): bool
        {
            return true;
        }

        public function sqliteCreateFunction($function_name, $callback, $num_args = -1): bool
        {
            return true;
        }
    }
}
