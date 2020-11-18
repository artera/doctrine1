<?php
namespace Tests\Export;

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
        $this->expectException(\Doctrine_Export_Exception::class);
        static::$conn->export->alterTable(0, [], []);
    }

    public function testCreateTableExecutesSql()
    {
        $name = 'mytable';

        $fields  = ['id' => ['type' => 'integer', 'unsigned' => 1]];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (id INT UNSIGNED) ENGINE = MYISAM');
    }

    public function testCreateTableSupportsDefaultTableType()
    {
        $name = 'mytable';

        $fields = ['id' => ['type' => 'integer', 'unsigned' => 1]];

        static::$conn->export->createTable($name, $fields);

        // INNODB is the default type
        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (id INT UNSIGNED) ENGINE = INNODB');
    }

    public function testCreateTableSupportsMultiplePks()
    {
        $name   = 'mytable';
        $fields = ['name'  => ['type' => 'char', 'length' => 10],
                         'type' => ['type' => 'integer', 'length' => 3]];

        $options = ['primary' => ['name', 'type']];
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (name CHAR(10), type MEDIUMINT, PRIMARY KEY(name, type)) ENGINE = INNODB');
    }

    public function testCreateTableSupportsAutoincPks()
    {
        $name = 'mytable';

        $fields  = ['id' => ['type' => 'integer', 'unsigned' => 1, 'autoincrement' => true]];
        $options = ['primary' => ['id'],
                        'type'     => 'INNODB'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (id INT UNSIGNED AUTO_INCREMENT, PRIMARY KEY(id)) ENGINE = INNODB');
    }

    public function testCreateTableSupportsCharType()
    {
        $name = 'mytable';

        $fields  = ['id' => ['type' => 'char', 'length' => 3]];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (id CHAR(3)) ENGINE = MYISAM');
    }

    public function testCreateTableSupportsCharType2()
    {
        $name = 'mytable';

        $fields  = ['id' => ['type' => 'char']];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (id CHAR(255)) ENGINE = MYISAM');
    }

    public function testCreateTableSupportsVarcharType()
    {
        $name = 'mytable';

        $fields  = ['id' => ['type' => 'varchar', 'length' => '100']];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (id VARCHAR(100)) ENGINE = MYISAM');
    }

    public function testCreateTableSupportsIntegerType()
    {
        $name = 'mytable';

        $fields  = ['id' => ['type' => 'integer', 'length' => '10']];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (id BIGINT) ENGINE = MYISAM');
    }

    public function testCreateTableSupportsBlobType()
    {
        $name = 'mytable';

        $fields  = ['content' => ['type' => 'blob']];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (content LONGBLOB) ENGINE = MYISAM');
    }

    public function testCreateTableSupportsBlobType2()
    {
        $name = 'mytable';

        $fields  = ['content' => ['type' => 'blob', 'length' => 2000]];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (content BLOB) ENGINE = MYISAM');
    }

    public function testCreateTableSupportsBooleanType()
    {
        $name = 'mytable';

        $fields  = ['id' => ['type' => 'boolean']];
        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE mytable (id TINYINT(1)) ENGINE = MYISAM');
    }

    public function testCreateTableSupportsEnumType()
    {
        $name = 'mytable';

        $fields = [
            'letter' => [
                'type'    => 'enum',
                'values'  => ['a', 'b', 'c'],
                'default' => 'a',
                'notnull' => true,
                'length'  => '1',
            ]
        ];

        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        // Native enum support not enabled, should be VARCHAR
        $this->assertEquals(
            static::$adapter->pop(),
            "CREATE TABLE mytable (letter VARCHAR(1) DEFAULT 'a' NOT NULL) ENGINE = MYISAM"
        );

        static::$conn->setAttribute(\Doctrine_Core::ATTR_USE_NATIVE_ENUM, true);
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(
            static::$adapter->pop(),
            "CREATE TABLE mytable (letter ENUM('a', 'b', 'c') DEFAULT 'a' NOT NULL) ENGINE = MYISAM"
        );
    }

    public function testCreateTableSupportsSetType()
    {
        $name = 'mytable';

        $fields = [
            'letter' => [
                'type'    => 'set',
                'values'  => ['a', 'b', 'c'],
                'default' => 'a',
                'notnull' => true,
            ]
        ];

        $options = ['type' => 'MYISAM'];

        static::$conn->export->createTable($name, $fields, $options);

        // Native Set not enabled, should be VARCHAR
        $this->assertEquals(
            static::$adapter->pop(),
            "CREATE TABLE mytable (letter VARCHAR(5) DEFAULT 'a' NOT NULL) ENGINE = MYISAM"
        );

        static::$conn->setAttribute(\Doctrine_Core::ATTR_USE_NATIVE_SET, true);
        static::$conn->export->createTable($name, $fields, $options);

        $this->assertEquals(
            static::$adapter->pop(),
            "CREATE TABLE mytable (letter SET('a', 'b', 'c') DEFAULT 'a' NOT NULL) ENGINE = MYISAM"
        );
    }

    public function testCreateTableSupportsForeignKeys()
    {
        $name = 'mytable';

        $fields = ['id'         => ['type' => 'boolean', 'primary' => true],
                        'foreignKey' => ['type' => 'integer']
                        ];
        $options = ['type'        => 'INNODB',
                         'foreignKeys' => [['local'        => 'foreignKey',
                                                      'foreign'      => 'id',
                                                      'foreignTable' => 'sometable']]
                         ];


        $sql = static::$conn->export->createTableSql($name, $fields, $options);

        $this->assertEquals($sql[0], 'CREATE TABLE mytable (id TINYINT(1), foreignKey INT, INDEX foreignKey_idx (foreignKey)) ENGINE = INNODB');
        $this->assertEquals($sql[1], 'ALTER TABLE mytable ADD FOREIGN KEY (foreignKey) REFERENCES sometable(id)');
    }

    public function testForeignKeyIdentifierQuoting()
    {
        static::$conn->setAttribute(\Doctrine_Core::ATTR_QUOTE_IDENTIFIER, true);

        $name = 'mytable';

        $fields = ['id'         => ['type' => 'boolean', 'primary' => true],
                        'foreignKey' => ['type' => 'integer']
                        ];
        $options = ['type'        => 'INNODB',
                         'foreignKeys' => [['local'        => 'foreignKey',
                                                      'foreign'      => 'id',
                                                      'foreignTable' => 'sometable']]
                         ];


        $sql = static::$conn->export->createTableSql($name, $fields, $options);

        $this->assertEquals($sql[0], 'CREATE TABLE `mytable` (`id` TINYINT(1), `foreignKey` INT, INDEX `foreignKey_idx` (`foreignKey`)) ENGINE = INNODB');
        $this->assertEquals($sql[1], 'ALTER TABLE `mytable` ADD FOREIGN KEY (`foreignKey`) REFERENCES `sometable`(`id`)');

        static::$conn->setAttribute(\Doctrine_Core::ATTR_QUOTE_IDENTIFIER, false);
    }

    public function testIndexIdentifierQuoting()
    {
        static::$conn->setAttribute(\Doctrine_Core::ATTR_QUOTE_IDENTIFIER, true);

        $fields = ['id'    => ['type' => 'integer', 'unsigned' => 1, 'autoincrement' => true],
                         'name' => ['type' => 'string', 'length' => 4],
                         ];

        $options = ['primary' => ['id'],
                         'indexes' => ['myindex' => ['fields' => ['id', 'name']]]
                         ];

        static::$conn->export->createTable('sometable', $fields, $options);

        //this was the old line, but it looks like the table is created first
        //and then the index so i replaced it with the ones below
        //$this->assertEquals($var, 'CREATE TABLE sometable (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(4), INDEX myindex (id, name))');

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE `sometable` (`id` INT UNSIGNED AUTO_INCREMENT, `name` VARCHAR(4), INDEX `myindex_idx` (`id`, `name`), PRIMARY KEY(`id`)) ENGINE = INNODB');

        static::$conn->setAttribute(\Doctrine_Core::ATTR_QUOTE_IDENTIFIER, false);
    }

    public function testCreateTableDoesNotAutoAddIndexesWhenIndexForFkFieldAlreadyExists()
    {
        $name = 'mytable';

        $fields = ['id'         => ['type' => 'boolean', 'primary' => true],
                        'foreignKey' => ['type' => 'integer']
                        ];
        $options = ['type'        => 'INNODB',
                         'foreignKeys' => [['local'        => 'foreignKey',
                                                      'foreign'      => 'id',
                                                      'foreignTable' => 'sometable']],
                         'indexes' => ['myindex' => ['fields' => ['foreignKey']]],
                         ];


        $sql = static::$conn->export->createTableSql($name, $fields, $options);
        $this->assertEquals($sql[0], 'CREATE TABLE mytable (id TINYINT(1), foreignKey INT, INDEX myindex_idx (foreignKey)) ENGINE = INNODB');
        $this->assertEquals($sql[1], 'ALTER TABLE mytable ADD FOREIGN KEY (foreignKey) REFERENCES sometable(id)');
    }

    public function testCreateDatabaseExecutesSql()
    {
        static::$conn->export->createDatabase('db');

        $this->assertEquals(static::$adapter->pop(), 'CREATE DATABASE db');
    }

    public function testDropDatabaseExecutesSql()
    {
        static::$conn->export->dropDatabase('db');

        $this->assertEquals(static::$adapter->pop(), 'SET FOREIGN_KEY_CHECKS = 1');
        $this->assertEquals(static::$adapter->pop(), 'DROP DATABASE db');
        $this->assertEquals(static::$adapter->pop(), 'SET FOREIGN_KEY_CHECKS = 0');
    }

    public function testDropIndexExecutesSql()
    {
        static::$conn->export->dropIndex('sometable', 'relevancy');

        $this->assertEquals(static::$adapter->pop(), 'DROP INDEX relevancy_idx ON sometable');
    }

    public function testUnknownIndexSortingAttributeThrowsException()
    {
        $fields = ['id'   => ['sorting' => 'ASC'],
                        'name' => ['sorting' => 'unknown']];

                        $this->expectException(\Doctrine_Export_Exception::class);
        static::$conn->export->getIndexFieldDeclarationList($fields);
    }

    public function testIndexDeclarationsSupportSortingAndLengthAttributes()
    {
        $fields = ['id'   => ['sorting' => 'ASC', 'length' => 10],
                        'name' => ['sorting' => 'DESC', 'length' => 1]];

        $this->assertEquals(static::$conn->export->getIndexFieldDeclarationList($fields), 'id(10) ASC, name(1) DESC');
    }

    public function testCreateTableSupportsIndexesUsingSingleFieldString()
    {
        $fields = ['id'    => ['type' => 'integer', 'unsigned' => 1, 'autoincrement' => true],
                         'name' => ['type' => 'string', 'length' => 4],
                         ];

        $options = ['primary' => ['id'],
                         'indexes' => ['myindex' => [
                                                    'fields' => 'name']]
                         ];

        static::$conn->export->createTable('sometable', $fields, $options);
        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE sometable (id INT UNSIGNED AUTO_INCREMENT, name VARCHAR(4), INDEX myindex_idx (name), PRIMARY KEY(id)) ENGINE = INNODB');
    }

    public function testCreateTableSupportsIndexesWithCustomSorting()
    {
        $fields = ['id'    => ['type' => 'integer', 'unsigned' => 1, 'autoincrement' => true],
                         'name' => ['type' => 'string', 'length' => 4],
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

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE sometable (id INT UNSIGNED AUTO_INCREMENT, name VARCHAR(4), INDEX myindex_idx (id ASC, name DESC), PRIMARY KEY(id)) ENGINE = INNODB');
    }
    public function testCreateTableSupportsFulltextIndexes()
    {
        $fields = ['id'       => ['type' => 'integer', 'unsigned' => 1, 'autoincrement' => true],
                         'content' => ['type' => 'string', 'length' => 4],
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

        $this->assertEquals(static::$adapter->pop(), 'CREATE TABLE sometable (id INT UNSIGNED AUTO_INCREMENT, content VARCHAR(4), FULLTEXT INDEX myindex_idx (content DESC), PRIMARY KEY(id)) ENGINE = MYISAM');
    }

    public function testCreateTableSupportsCompoundForeignKeys()
    {
        $name = 'mytable';

        $fields = ['id'   => ['type' => 'boolean', 'primary' => true],
                        'lang' => ['type' => 'integer', 'primary' => true]
                        ];
        $options = ['type'        => 'INNODB',
                         'foreignKeys' => [['local'        => ['id', 'lang' ],
                                                      'foreign'      => ['id', 'lang'],
                                                      'foreignTable' => 'sometable']]
                         ];

        $sql = static::$conn->export->createTableSql($name, $fields, $options);

        $this->assertEquals($sql[0], 'CREATE TABLE mytable (id TINYINT(1), lang INT, INDEX id_idx (id), INDEX lang_idx (lang)) ENGINE = INNODB');
        $this->assertEquals($sql[1], 'ALTER TABLE mytable ADD FOREIGN KEY (id, lang) REFERENCES sometable(id, lang)');
    }

    public function testCreateTableSupportsFieldCharset()
    {
        $sql = static::$conn->export->createTableSql(
            'mytable',
            [
            'name' => ['type' => 'string', 'length' => 255, 'charset' => 'utf8'],
            ]
        );

        $this->assertEquals($sql[0], 'CREATE TABLE mytable (name VARCHAR(255) CHARACTER SET utf8) ENGINE = INNODB');
    }

    public function testCreateTableSupportsFieldCollation()
    {
        $sql = static::$conn->export->createTableSql(
            'mytable',
            [
            'name' => ['type' => 'string', 'length' => 255, 'collation' => 'utf8_general_ci'],
            ]
        );

        $this->assertEquals($sql[0], 'CREATE TABLE mytable (name VARCHAR(255) COLLATE utf8_general_ci) ENGINE = INNODB');
    }
}
