<?php

namespace Tests\Export;

use Doctrine1\Column;
use Doctrine1\Column\Type;
use Tests\DoctrineUnitTestCase;

class MysqlTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Mysql';

    public static function prepareTables(): void
    {
    }

    public static function prepareData(): void
    {
    }

    public function testAlterTableThrowsExceptionWithoutValidTableName()
    {
        $this->expectException(\Doctrine1\Export\Exception::class);
        static::$conn->export->alterTable(0, [], []);
    }

    public function testCreateTableExecutesSql()
    {
        $name = 'mytable';

        $fields = [
            new Column('id', Type::Integer, unsigned: true),
        ];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals('CREATE TABLE mytable (id BIGINT UNSIGNED) ENGINE = MYISAM', static::$adapter->pop());
    }

    public function testCreateTableSupportsDefaultTableType()
    {
        $name = 'mytable';

        $fields = [
            new Column('id', Type::Integer, unsigned: true),
        ];

        static::$conn->export->createTable($name, $fields);

        // InnoDB is the default type
        $this->assertEquals('CREATE TABLE mytable (id BIGINT UNSIGNED) ENGINE = InnoDB', static::$adapter->pop());
    }

    public function testCreateTableSupportsMultiplePks()
    {
        $name   = 'mytable';
        $fields = [
            new Column('name', Type::String, 10, fixed: true),
            new Column('type', Type::Integer, 3),
        ];

        $options = ['primary' => ['name', 'type']];
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals('CREATE TABLE mytable (name CHAR(10), type MEDIUMINT, PRIMARY KEY(name, type)) ENGINE = InnoDB', static::$adapter->pop());
    }

    public function testCreateTableSupportsAutoincPks()
    {
        $name = 'mytable';

        $fields = [
            new Column('id', Type::Integer, unsigned: true, autoincrement: true),
        ];
        $options = ['primary' => ['id'],
                        'type'     => 'InnoDB'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals('CREATE TABLE mytable (id BIGINT UNSIGNED AUTO_INCREMENT, PRIMARY KEY(id)) ENGINE = InnoDB', static::$adapter->pop());
    }

    public function testCreateTableSupportsCharType()
    {
        $name = 'mytable';

        $fields = [
            new Column('id', Type::String, 3, fixed: true),
        ];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals('CREATE TABLE mytable (id CHAR(3)) ENGINE = MYISAM', static::$adapter->pop());
    }

    public function testCreateTableSupportsCharType2()
    {
        $name = 'mytable';

        $fields = [
            new Column('id', Type::String, fixed: true),
        ];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals('CREATE TABLE mytable (id CHAR(255)) ENGINE = MYISAM', static::$adapter->pop());
    }

    public function testCreateTableSupportsVarcharType()
    {
        $name = 'mytable';

        $fields = [
            new Column('id', Type::String, 100),
        ];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals('CREATE TABLE mytable (id VARCHAR(100)) ENGINE = MYISAM', static::$adapter->pop());
    }

    public function testCreateTableSupportsIntegerType()
    {
        $name = 'mytable';

        $fields = [
            new Column('id', Type::Integer, 10),
        ];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals('CREATE TABLE mytable (id BIGINT) ENGINE = MYISAM', static::$adapter->pop());
    }

    public function testCreateTableSupportsBlobType()
    {
        $name = 'mytable';

        $fields = [
            new Column('content', Type::BLOB),
        ];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals('CREATE TABLE mytable (content LONGBLOB) ENGINE = MYISAM', static::$adapter->pop());
    }

    public function testCreateTableSupportsBlobType2()
    {
        $name = 'mytable';

        $fields = [
            new Column('content', Type::BLOB, 2000),
        ];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals('CREATE TABLE mytable (content BLOB) ENGINE = MYISAM', static::$adapter->pop());
    }

    public function testCreateTableSupportsBooleanType()
    {
        $name = 'mytable';

        $fields = [
            new Column('id', Type::Boolean),
        ];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals('CREATE TABLE mytable (id TINYINT(1)) ENGINE = MYISAM', static::$adapter->pop());
    }

    public function testCreateTableSupportsEnumType()
    {
        $name = 'mytable';

        $fields = [
            new Column('letter', Type::Enum, 1, values: ['a', 'b', 'c'], default: 'a', notnull: true),
        ];

        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        // Native enum support not enabled, should be VARCHAR
        $this->assertEquals(
            "CREATE TABLE mytable (letter VARCHAR(1) DEFAULT 'a' NOT NULL) ENGINE = MYISAM",
            static::$adapter->pop(),
        );

        static::$conn->setUseNativeEnum(true);
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(
            "CREATE TABLE mytable (letter ENUM('a', 'b', 'c') DEFAULT 'a' NOT NULL) ENGINE = MYISAM",
            static::$adapter->pop(),
        );
    }

    public function testCreateTableSupportsSetType()
    {
        $name = 'mytable';

        $fields = [
            new Column('letter', Type::Set, values: ['a', 'b', 'c'], default: 'a', notnull: true),
        ];

        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        // Native Set not enabled, should be VARCHAR
        $this->assertEquals(
            "CREATE TABLE mytable (letter VARCHAR(5) DEFAULT 'a' NOT NULL) ENGINE = MYISAM",
            static::$adapter->pop(),
        );

        static::$conn->setUseNativeSet(true);
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(
            "CREATE TABLE mytable (letter SET('a', 'b', 'c') DEFAULT 'a' NOT NULL) ENGINE = MYISAM",
            static::$adapter->pop(),
        );
    }

    public function testCreateTableSupportsForeignKeys()
    {
        $name = 'mytable';

        $fields = [
            new Column('id', Type::Boolean, primary: true),
            new Column('foreignKey', Type::Integer),
        ];
        $options = ['type'        => 'InnoDB',
                         'foreignKeys' => [['local'        => 'foreignKey',
                                                      'foreign'      => 'id',
                                                      'foreignTable' => 'sometable']]
                         ];


        $sql = static::$conn->export->createTableSql($name, $fields, $options);

        $this->assertEquals('CREATE TABLE mytable (id TINYINT(1), foreignKey BIGINT, INDEX foreignKey_idx (foreignKey)) ENGINE = InnoDB', $sql[0]);
        $this->assertEquals('ALTER TABLE mytable ADD FOREIGN KEY (foreignKey) REFERENCES sometable(id)', $sql[1]);
    }

    public function testForeignKeyIdentifierQuoting()
    {
        static::$conn->setQuoteIdentifier(true);

        $name = 'mytable';

        $fields = [
            new Column('id', Type::Boolean, primary: true),
            new Column('foreignKey', Type::Integer),
        ];
        $options = ['type'        => 'InnoDB',
                         'foreignKeys' => [['local'        => 'foreignKey',
                                                      'foreign'      => 'id',
                                                      'foreignTable' => 'sometable']]
                         ];


        $sql = static::$conn->export->createTableSql($name, $fields, $options);

        $this->assertEquals('CREATE TABLE `mytable` (`id` TINYINT(1), `foreignKey` BIGINT, INDEX `foreignKey_idx` (`foreignKey`)) ENGINE = InnoDB', $sql[0]);
        $this->assertEquals('ALTER TABLE `mytable` ADD FOREIGN KEY (`foreignKey`) REFERENCES `sometable`(`id`)', $sql[1]);

        static::$conn->setQuoteIdentifier(false);
    }

    public function testIndexIdentifierQuoting()
    {
        static::$conn->setQuoteIdentifier(true);

        $fields = [
            new Column('id', Type::Integer, unsigned: true, autoincrement: true),
            new Column('name', Type::String, length: 4),
        ];

        $options = ['primary' => ['id'],
                         'indexes' => ['myindex' => ['fields' => ['id', 'name']]]
                         ];

        static::$conn->export->createTable('sometable', $fields, $options);

        //this was the old line, but it looks like the table is created first
        //and then the index so i replaced it with the ones below
        //$this->assertEquals($var, 'CREATE TABLE sometable (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(4), INDEX myindex (id, name))');

        $this->assertEquals('CREATE TABLE `sometable` (`id` BIGINT UNSIGNED AUTO_INCREMENT, `name` VARCHAR(4), INDEX `myindex_idx` (`id`, `name`), PRIMARY KEY(`id`)) ENGINE = InnoDB', static::$adapter->pop());

        static::$conn->setQuoteIdentifier(false);
    }

    public function testCreateTableDoesNotAutoAddIndexesWhenIndexForFkFieldAlreadyExists()
    {
        $name = 'mytable';

        $fields = [
            new Column('id', Type::Boolean, primary: true),
            new Column('foreignKey', Type::Integer),
        ];
        $options = ['type'        => 'InnoDB',
                         'foreignKeys' => [['local'        => 'foreignKey',
                                                      'foreign'      => 'id',
                                                      'foreignTable' => 'sometable']],
                         'indexes' => ['myindex' => ['fields' => ['foreignKey']]],
                         ];


        $sql = static::$conn->export->createTableSql($name, $fields, $options);
        $this->assertEquals('CREATE TABLE mytable (id TINYINT(1), foreignKey BIGINT, INDEX myindex_idx (foreignKey)) ENGINE = InnoDB', $sql[0]);
        $this->assertEquals('ALTER TABLE mytable ADD FOREIGN KEY (foreignKey) REFERENCES sometable(id)', $sql[1]);
    }

    public function testCreateDatabaseExecutesSql()
    {
        static::$conn->export->createDatabase('db');

        $this->assertEquals(static::$adapter->pop(), 'CREATE DATABASE db');
    }

    public function testDropDatabaseExecutesSql()
    {
        static::$conn->export->dropDatabase('db');

        $this->assertEquals('SET FOREIGN_KEY_CHECKS = 1', static::$adapter->pop());
        $this->assertEquals('DROP DATABASE db', static::$adapter->pop());
        $this->assertEquals('SET FOREIGN_KEY_CHECKS = 0', static::$adapter->pop());
    }

    public function testDropIndexExecutesSql()
    {
        static::$conn->export->dropIndex('sometable', 'relevancy');

        $this->assertEquals('DROP INDEX relevancy_idx ON sometable', static::$adapter->pop());
    }

    public function testUnknownIndexSortingAttributeThrowsException()
    {
        $fields = ['id'   => ['sorting' => 'ASC'],
                        'name' => ['sorting' => 'unknown']];

        $this->expectException(\Doctrine1\Export\Exception::class);
        static::$conn->export->getIndexFieldDeclarationList($fields);
    }

    public function testIndexDeclarationsSupportSortingAndLengthAttributes()
    {
        $fields = ['id'   => ['sorting' => 'ASC', 'length' => 10],
                        'name' => ['sorting' => 'DESC', 'length' => 1]];

        $this->assertEquals('id(10) ASC, name(1) DESC', static::$conn->export->getIndexFieldDeclarationList($fields));
    }

    public function testCreateTableSupportsIndexesUsingSingleFieldString()
    {
        $fields = [
            new Column('id', Type::Integer, unsigned: true, autoincrement: true),
            new Column('name', Type::String, 4),
        ];

        $options = ['primary' => ['id'],
                         'indexes' => ['myindex' => [
                                                    'fields' => ['name']]]
                         ];

        static::$conn->export->createTable('sometable', $fields, $options);
        $this->assertEquals('CREATE TABLE sometable (id BIGINT UNSIGNED AUTO_INCREMENT, name VARCHAR(4), INDEX myindex_idx (name), PRIMARY KEY(id)) ENGINE = InnoDB', static::$adapter->pop());
    }

    public function testCreateTableSupportsIndexesWithCustomSorting()
    {
        $fields = [
            new Column('id', Type::Integer, unsigned: true, autoincrement: true),
            new Column('name', Type::String, 4),
        ];

        $options = ['primary' => ['id'],
                         'indexes' => ['myindex' => [
                                                    'fields' => [
                                                            'id'   => ['sorting' => 'ASC'],
                                                            'name' => ['sorting' => 'DESC']
                                                                ]
                                                            ]]
                         ];

        static::$conn->export->createTable('sometable', $fields, $options);

        $this->assertEquals('CREATE TABLE sometable (id BIGINT UNSIGNED AUTO_INCREMENT, name VARCHAR(4), INDEX myindex_idx (id ASC, name DESC), PRIMARY KEY(id)) ENGINE = InnoDB', static::$adapter->pop());
    }
    public function testCreateTableSupportsFulltextIndexes()
    {
        $fields = [
            new Column('id', Type::Integer, unsigned: true, autoincrement: true),
            new Column('content', Type::String, 4),
        ];

        $options = ['primary' => ['id'],
                         'indexes' => ['myindex' => [
                                                    'fields' => [
                                                            'content' => ['sorting' => 'DESC']
                                                                ],
                                                    'type' => 'fulltext',
                                                            ]],
                         'type' => 'MYISAM',
                         ];

        static::$conn->export->createTable('sometable', $fields, $options);

        $this->assertEquals('CREATE TABLE sometable (id BIGINT UNSIGNED AUTO_INCREMENT, content VARCHAR(4), FULLTEXT INDEX myindex_idx (content DESC), PRIMARY KEY(id)) ENGINE = MYISAM', static::$adapter->pop());
    }

    public function testCreateTableSupportsCompoundForeignKeys()
    {
        $name = 'mytable';

        $fields = [
            new Column('id', Type::Boolean, primary: true),
            new Column('lang', Type::Integer, primary: true),
        ];
        $options = ['type'        => 'InnoDB',
                         'foreignKeys' => [['local'        => ['id', 'lang' ],
                                                      'foreign'      => ['id', 'lang'],
                                                      'foreignTable' => 'sometable']]
                         ];

        $sql = static::$conn->export->createTableSql($name, $fields, $options);

        $this->assertEquals('CREATE TABLE mytable (id TINYINT(1), lang BIGINT, INDEX id_idx (id), INDEX lang_idx (lang)) ENGINE = InnoDB', $sql[0]);
        $this->assertEquals('ALTER TABLE mytable ADD FOREIGN KEY (id, lang) REFERENCES sometable(id, lang)', $sql[1]);
    }

    public function testCreateTableSupportsFieldCharset()
    {
        $sql = static::$conn->export->createTableSql(
            'mytable',
            [
                new Column('name', Type::String, 255, charset: 'utf8'),
            ]
        );

        $this->assertEquals('CREATE TABLE mytable (name VARCHAR(255) CHARACTER SET utf8) ENGINE = InnoDB', $sql[0]);
    }

    public function testCreateTableSupportsFieldCollation()
    {
        $sql = static::$conn->export->createTableSql(
            'mytable',
            [
                new Column('name', Type::String, 255, collation: 'utf8_general_ci'),
            ]
        );

        $this->assertEquals('CREATE TABLE mytable (name VARCHAR(255) COLLATE utf8_general_ci) ENGINE = InnoDB', $sql[0]);
    }
}
