<?php

namespace Doctrine1\DataDict;

use Doctrine1\Column;
use Doctrine1\Column\Type;

class Mysql extends \Doctrine1\DataDict
{
    /**
     * @var array
     */
    protected $keywords = [
        'ACCESSIBLE',
        'ADD',
        'ALL',
        'ALTER',
        'ANALYZE',
        'AND',
        'AS',
        'ASC',
        'ASENSITIVE',
        'BEFORE',
        'BETWEEN',
        'BIGINT',
        'BINARY',
        'BLOB',
        'BOTH',
        'BY',
        'CALL',
        'CASCADE',
        'CASE',
        'CHANGE',
        'CHAR',
        'CHARACTER',
        'CHECK',
        'CLUSTERING',
        'COLLATE',
        'COLUMN',
        'CONDITION',
        'CONSTRAINT',
        'CONTINUE',
        'CONVERT',
        'CREATE',
        'CROSS',
        'CUBE',
        'CUME_DIST',
        'CURRENT_DATE',
        'CURRENT_TIME',
        'CURRENT_TIMESTAMP',
        'CURRENT_USER',
        'CURSOR',
        'DATABASE',
        'DATABASES',
        'DAY_HOUR',
        'DAY_MICROSECOND',
        'DAY_MINUTE',
        'DAY_SECOND',
        'DEC',
        'DECIMAL',
        'DECLARE',
        'DEFAULT',
        'DELAYED',
        'DELETE',
        'DENSE_RANK',
        'DESC',
        'DESCRIBE',
        'DETERMINISTIC',
        'DISTINCT',
        'DISTINCTROW',
        'DIV',
        'DOUBLE',
        'DROP',
        'DUAL',
        'EACH',
        'ELSE',
        'ELSEIF',
        'EMPTY',
        'ENCLOSED',
        'ESCAPED',
        'EXCEPT',
        'EXISTS',
        'EXIT',
        'EXPLAIN',
        'FALSE',
        'FETCH',
        'FIRST_VALUE',
        'FLOAT',
        'FLOAT4',
        'FLOAT8',
        'FOR',
        'FORCE',
        'FOREIGN',
        'FROM',
        'FULLTEXT',
        'FUNCTION',
        'GENERATED',
        'GET',
        'GRANT',
        'GROUP',
        'GROUPING',
        'GROUPS',
        'HAVING',
        'HIGH_PRIORITY',
        'HOUR_MICROSECOND',
        'HOUR_MINUTE',
        'HOUR_SECOND',
        'IF',
        'IGNORE',
        'IN',
        'INDEX',
        'INFILE',
        'INNER',
        'INOUT',
        'INSENSITIVE',
        'INSERT',
        'INT',
        'INT1',
        'INT2',
        'INT3',
        'INT4',
        'INT8',
        'INTEGER',
        'INTERVAL',
        'INTO',
        'IO_AFTER_GTIDS',
        'IO_BEFORE_GTIDS',
        'IS',
        'ITERATE',
        'JOIN',
        'JSON_TABLE',
        'KEY',
        'KEYS',
        'KILL',
        'LAG',
        'LAST_VALUE',
        'LATERAL',
        'LEAD',
        'LEADING',
        'LEAVE',
        'LEFT',
        'LIKE',
        'LIMIT',
        'LINEAR',
        'LINES',
        'LOAD',
        'LOCALTIME',
        'LOCALTIMESTAMP',
        'LOCK',
        'LONG',
        'LONGBLOB',
        'LONGTEXT',
        'LOOP',
        'LOW_PRIORITY',
        'MASTER_BIND',
        'MASTER_SSL_VERIFY_SERVER_CERT',
        'MATCH',
        'MAXVALUE',
        'MEDIUMBLOB',
        'MEDIUMINT',
        'MEDIUMTEXT',
        'MIDDLEINT',
        'MINUTE_MICROSECOND',
        'MINUTE_SECOND',
        'MOD',
        'MODIFIES',
        'NATURAL',
        'NOT',
        'NO_WRITE_TO_BINLOG',
        'NTH_VALUE',
        'NTILE',
        'NULL',
        'NUMERIC',
        'OF',
        'ON',
        'OPTIMIZE',
        'OPTIMIZER_COSTS',
        'OPTION',
        'OPTIONALLY',
        'OR',
        'ORDER',
        'OUT',
        'OUTER',
        'OUTFILE',
        'OVER',
        'PARTITION',
        'PERCENT_RANK',
        'PERSIST',
        'PERSIST_ONLY',
        'PRECISION',
        'PRIMARY',
        'PROCEDURE',
        'PURGE',
        'RANGE',
        'RANK',
        'READ',
        'READS',
        'READ_WRITE',
        'REAL',
        'RECURSIVE',
        'REFERENCES',
        'REGEXP',
        'RELEASE',
        'RENAME',
        'REPEAT',
        'REPLACE',
        'REQUIRE',
        'RESIGNAL',
        'RESTRICT',
        'RETURN',
        'REVOKE',
        'RIGHT',
        'RLIKE',
        'ROW',
        'ROWS',
        'ROW_NUMBER',
        'SCHEMA',
        'SCHEMAS',
        'SECOND_MICROSECOND',
        'SELECT',
        'SENSITIVE',
        'SEPARATOR',
        'SET',
        'SHOW',
        'SIGNAL',
        'SMALLINT',
        'SPATIAL',
        'SPECIFIC',
        'SQL',
        'SQLEXCEPTION',
        'SQLSTATE',
        'SQLWARNING',
        'SQL_BIG_RESULT',
        'SQL_CALC_FOUND_ROWS',
        'SQL_SMALL_RESULT',
        'SSL',
        'STARTING',
        'STORED',
        'STRAIGHT_JOIN',
        'SYSTEM',
        'TABLE',
        'TERMINATED',
        'THEN',
        'TINYBLOB',
        'TINYINT',
        'TINYTEXT',
        'TO',
        'TRAILING',
        'TRIGGER',
        'TRUE',
        'UNDO',
        'UNION',
        'UNIQUE',
        'UNLOCK',
        'UNSIGNED',
        'UPDATE',
        'USAGE',
        'USE',
        'USING',
        'UTC_DATE',
        'UTC_TIME',
        'UTC_TIMESTAMP',
        'VALUES',
        'VARBINARY',
        'VARCHAR',
        'VARCHARACTER',
        'VARYING',
        'VIRTUAL',
        'WHEN',
        'WHERE',
        'WHILE',
        'WINDOW',
        'WITH',
        'WRITE',
        'XOR',
        'YEAR_MONTH',
        'ZEROFILL',
    ];

    public function getNativeDeclaration(Column $field): string
    {
        if (!isset($field->type)) {
            throw new \Doctrine1\DataDict\Exception('Missing column type.');
        }

        $length = $field->length;

        switch ($field->type) {
            case Type::Enum:
            case Type::Set:
                $stringValues = $field->stringValues();

                if (($this->conn->getUseNativeSet() && $field->type === Type::Set)
                || ($this->conn->getUseNativeEnum() && $field->type === Type::Enum)
                ) {
                    $values = [];
                    foreach ($stringValues as $value) {
                        $values[] = $this->conn->quote($value, 'varchar');
                    }
                    return strtoupper($field->type->value) . '(' . implode(', ', $values) . ')';
                } else {
                    if ($field->type === Type::Enum && !empty($stringValues)) {
                        $length = max(array_map('strlen', $stringValues));
                    } elseif ($field->type === Type::Set && !empty($stringValues)) {
                        $length = strlen(implode(',', $stringValues));
                    } else {
                        $length = $field->length ?? 255;
                    }
                }
                // no break
            case Type::String:
                if (!isset($length)) {
                    if ($field->hasDefault()) {
                        $length = $this->conn->varchar_max_length;
                    } else {
                        $length = false;
                    }
                }

                $length = $field->length <= $this->conn->varchar_max_length ? $field->length : false;
                $fixed  = isset($field->fixed) ? $field->fixed : false;

                return $fixed ? ('CHAR(' . ($length ?? 255) . ')')
                    : ($length ? "VARCHAR($length)" : 'TEXT');
            case Type::Array:
            case Type::Object:
                if (isset($length)) {
                    if ($length <= 255) {
                        return 'TINYTEXT';
                    } elseif ($length <= 65532) {
                        return 'TEXT';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMTEXT';
                    }
                }
                return 'LONGTEXT';
            case Type::BLOB:
                if (isset($length)) {
                    if ($length <= 255) {
                        return 'TINYBLOB';
                    } elseif ($length <= 65532) {
                        return 'BLOB';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMBLOB';
                    }
                }
                return 'LONGBLOB';
            case Type::Integer:
                if (isset($length)) {
                    if ($length <= 1) {
                        return 'TINYINT';
                    } elseif ($length == 2) {
                        return 'SMALLINT';
                    } elseif ($length == 3) {
                        return 'MEDIUMINT';
                    } elseif ($length == 4) {
                        return 'INT';
                    } else {
                        return 'BIGINT';
                    }
                }
                return 'INT';
            case Type::Boolean:
                return 'TINYINT(1)';
            case Type::Date:
                return 'DATE';
            case Type::Time:
                return 'TIME';
            case Type::Timestamp:
                return 'DATETIME';
            case Type::Float:
                $length ??= 18;
                $scale  = !empty($field->scale) ? $field->scale : $this->conn->getDecimalPlaces();
                return "FLOAT($length, $scale)";
            case Type::Double:
                $length ??= 18;
                $scale  = !empty($field->scale) ? $field->scale : $this->conn->getDecimalPlaces();
                return "DOUBLE($length, $scale)";
            case Type::Decimal:
                $length ??= 18;
                $scale  = !empty($field->scale) ? $field->scale : $this->conn->getDecimalPlaces();
                return "DECIMAL($length, $scale)";
            case Type::Bit:
                return 'BIT';
        }
        return $field->type->value . (isset($length) ? '(' . $length . ')' : null);
    }

    /**
     * Maps a native array description of a field to a Mysql datatype and length
     *
     * @param  array $field native field description
     * @return array containing the various possible types, length, sign, fixed
     */
    public function getPortableDeclaration(array $field)
    {
        $dbType = strtolower($field['type']);
        $dbType = strtok($dbType, '(), ');
        if ($dbType === 'national') {
            $dbType = strtok('(), ');
        }
        assert($dbType !== false);
        if (isset($field['length'])) {
            $length  = $field['length'];
            $decimal = '';
        } else {
            $length  = strtok('(), ') ?: 0;
            $decimal = strtok('(), ') ?: null;
        }
        $type     = [];
        $unsigned = $fixed = null;

        if (!isset($field['name'])) {
            // Mysql's DESCRIBE returns a "Field" column, not a "Name" column
            // this method is called with output from that query in \Doctrine1\Import\Mysql::listTableColumns
            if (isset($field['field'])) {
                $field['name'] = $field['field'];
            } else {
                $field['name'] = '';
            }
        }

        $values = null;
        $scale  = null;

        switch ($dbType) {
            case 'tinyint':
                $type[] = 'integer';
                $type[] = 'boolean';
                if ((isset($field['comment']) && preg_match('/\bBOOL\b/', $field['comment'])) || preg_match('/^(is|has)/', $field['name'])) {
                    $type = array_reverse($type);
                }
                $unsigned = (bool) preg_match('/ unsigned/i', $field['type']);
                $length   = 1;
                break;
            case 'smallint':
                $type[]   = 'integer';
                $unsigned = (bool) preg_match('/ unsigned/i', $field['type']);
                $length   = 2;
                break;
            case 'mediumint':
                $type[]   = 'integer';
                $unsigned = (bool) preg_match('/ unsigned/i', $field['type']);
                $length   = 3;
                break;
            case 'int':
            case 'integer':
                $type[]   = 'integer';
                $unsigned = (bool) preg_match('/ unsigned/i', $field['type']);
                $length   = 4;
                break;
            case 'bigint':
                $type[]   = 'integer';
                $unsigned = (bool) preg_match('/ unsigned/i', $field['type']);
                $length   = 8;
                break;
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'text':
            case 'text':
            case 'varchar':
                $fixed = false;
                // no break
            case 'string':
            case 'char':
                $type[] = 'string';
                if (strstr($dbType, 'text')) {
                    $type[] = 'clob';
                    if ($decimal == 'binary') {
                        $type[] = 'blob';
                    }
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
                break;
            case 'enum':
            case 'set':
                $type[] = $dbType;
                preg_match_all('/\'((?:\'\'|[^\'])*)\'/', $field['type'], $matches);
                $length = 0;
                $fixed  = false;
                foreach ($matches[1] as &$value) {
                    $value  = str_replace('\'\'', '\'', $value);
                    $length = max($length, strlen($value));
                }
                if ($dbType === 'enum' && $length == '1' && count($matches[1]) == 2) {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                }

                if ($dbType === 'set') {
                    $length = strlen(implode(',', $matches[1]));
                }

                $values = $matches[1];
                $type[] = 'integer';
                break;
            case 'date':
                $type[] = 'date';
                $length = null;
                break;
            case 'datetime':
            case 'timestamp':
                $type[] = 'timestamp';
                $length = null;
                break;
            case 'time':
                $type[] = 'time';
                $length = null;
                break;
            case 'float':
            case 'double':
            case 'real':
                $type[]   = 'float';
                $unsigned = (bool) preg_match('/ unsigned/i', $field['type']);
                break;
            case 'unknown':
            case 'decimal':
                if ($decimal !== null) {
                    $scale = $decimal;
                }
                // no break
            case 'numeric':
                $type[]   = 'decimal';
                $unsigned = (bool) preg_match('/ unsigned/i', $field['type']);
                break;
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'blob':
            case 'binary':
            case 'varbinary':
                $type[] = 'blob';
                $length = null;
                break;
            case 'year':
                $type[] = 'integer';
                $type[] = 'date';
                $length = null;
                break;
            case 'bit':
                $type[] = 'bit';
                break;
            case 'geometry':
            case 'geometrycollection':
            case 'point':
            case 'multipoint':
            case 'linestring':
            case 'multilinestring':
            case 'polygon':
            case 'multipolygon':
                $type[] = 'blob';
                $length = null;
                break;
            default:
                $type[] = $field['type'];
                $length = $field['length'] ?? null;
        }

        $length = ((int) $length == 0) ? null : (int) $length;
        $def = ['type' => $type, 'length' => $length, 'unsigned' => $unsigned, 'fixed' => $fixed];
        if ($values !== null) {
            $def['values'] = $values;
        }
        if ($scale !== null) {
            $def['scale'] = $scale;
        }
        return $def;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the CHARACTER SET
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param  string $charset name of the charset
     * @return string  DBMS specific SQL code portion needed to set the CHARACTER SET
     *                 of a field declaration.
     */
    public function getCharsetFieldDeclaration(string $charset): string
    {
        return 'CHARACTER SET ' . $charset;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the COLLATION
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @param  string $collation name of the collation
     * @return string  DBMS specific SQL code portion needed to set the COLLATION
     *                 of a field declaration.
     */
    public function getCollationFieldDeclaration(string $collation): string
    {
        return 'COLLATE ' . $collation;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     */
    public function getIntegerDeclaration(Column $field): string
    {
        $unique  = $field->unique ? ' UNIQUE' : '';
        $default = $autoinc = '';
        if ($field->autoincrement) {
            $autoinc = ' AUTO_INCREMENT';
        } elseif ($field->hasDefault()) {
            $default = $field->default;
            if ($default === '') {
                $default = $field->notnull ? 0 : null;
            }

            $default = ' DEFAULT ' . ($default === null
                ? 'NULL'
                : $this->conn->quote($default));
        }

        $notnull  = $field->notnull ? ' NOT NULL' : '';
        $unsigned = $field->unsigned ? ' UNSIGNED' : '';
        $comment  = $field->comment ? ' COMMENT ' . $this->conn->quote($field->comment, 'text') : '';

        $name = $this->conn->quoteIdentifier($field->name, true);

        return $name . ' ' . $this->getNativeDeclaration($field) . $unsigned
            . $default . $unique . $notnull . $autoinc . $comment;
    }
}
