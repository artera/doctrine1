<?php

namespace Tests\Import;

use Tests\DoctrineUnitTestCase;

class PgsqlTest extends DoctrineUnitTestCase
{
    protected static ?string $driverName = 'Pgsql';

    public function testListSequencesExecutesSql()
    {
        static::$conn->import->listSequences('table');

        $this->assertEquals(
            "SELECT
                regexp_replace(relname, '_seq$', '')
            FROM
                pg_class
            WHERE relkind = 'S' AND relnamespace IN
                (SELECT oid FROM pg_namespace
                    WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema')",
            static::$adapter->pop(),
        );
    }
    public function testListTableColumnsExecutesSql()
    {
        static::$conn->import->listTableColumns('table');

        $this->assertEquals(
            "SELECT
                ordinal_position as attnum,
                column_name as field,
                udt_name as type,
                data_type as complete_type,
                is_nullable as isnotnull,
                column_default as default,
                (
                SELECT 't'
                    FROM pg_index, pg_attribute a, pg_class c, pg_type t
                    WHERE c.relname = table_name AND a.attname = column_name
                    AND a.attnum > 0 AND a.attrelid = c.oid AND a.atttypid = t.oid
                    AND c.oid = pg_index.indrelid AND a.attnum = ANY (pg_index.indkey)
                    AND pg_index.indisprimary = 't'
                ) as pri,
                character_maximum_length as length
            FROM information_schema.COLUMNS
            WHERE table_name = 'table'
            ORDER BY ordinal_position",
            static::$adapter->pop(),
        );
    }
    public function testListTableIndexesExecutesSql()
    {
        static::$conn->import->listTableIndexes('table');

        $this->assertEquals(
            "SELECT
                relname
            FROM
                pg_class
            WHERE oid IN (
                SELECT indexrelid
                FROM pg_index, pg_class
                WHERE pg_class.relname = 'table'
                    AND pg_class.oid=pg_index.indrelid
                    AND indisunique != 't'
                    AND indisprimary != 't'
                )",
            static::$adapter->pop(),
        );
    }
    public function testListTablesExecutesSql()
    {
        static::$conn->import->listTables();

        $q = "SELECT
                c.relname AS table_name
            FROM pg_class c, pg_user u
            WHERE c.relowner = u.usesysid
                AND c.relkind = 'r'
                AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname AND schemaname <> 'information_schema')
                AND c.relname !~ '^(pg_|sql_)'
            UNION
            SELECT c.relname AS table_name
            FROM pg_class c
            WHERE c.relkind = 'r'
                AND NOT EXISTS (SELECT 1 FROM pg_views WHERE viewname = c.relname)
                AND NOT EXISTS (SELECT 1 FROM pg_user WHERE usesysid = c.relowner)
                AND c.relname !~ '^pg_'";
        $this->assertEquals($q, static::$adapter->pop());
    }
    public function testListDatabasesExecutesSql()
    {
        static::$conn->import->listDatabases();

        $q = 'SELECT datname FROM pg_database';
        $this->assertEquals(static::$adapter->pop(), $q);
    }
    public function testListUsersExecutesSql()
    {
        static::$conn->import->listUsers();

        $q = 'SELECT usename FROM pg_user';
        $this->assertEquals(static::$adapter->pop(), $q);
    }
    public function testListViewsExecutesSql()
    {
        static::$conn->import->listViews();

        $q = 'SELECT viewname FROM pg_views';
        $this->assertEquals(static::$adapter->pop(), $q);
    }
    public function testListFunctionsExecutesSql()
    {
        static::$conn->import->listFunctions();

        $q = "SELECT
                proname
            FROM
                pg_proc pr,
                pg_type tp
            WHERE
                tp.oid = pr.prorettype
                AND pr.proisagg = FALSE
                AND tp.typname <> 'trigger'
                AND pr.pronamespace IN
                    (SELECT oid FROM pg_namespace
                        WHERE nspname NOT LIKE 'pg_%' AND nspname != 'information_schema'";
        $this->assertEquals($q, static::$adapter->pop());
    }
    public function testListTableConstraintsExecutesSql()
    {
        static::$conn->import->listTableConstraints('table');


        $q = "SELECT
                relname
            FROM
                pg_class
            WHERE oid IN (
                SELECT indexrelid
                FROM pg_index, pg_class
                WHERE pg_class.relname = 'table'
                    AND pg_class.oid = pg_index.indrelid
                    AND (indisunique = 't' OR indisprimary = 't')
                )";
        $this->assertEquals($q, static::$adapter->pop());
    }
}
