<?php

namespace Doctrine1\DataDict;

use Doctrine1\Column;
use Doctrine1\Column\Type;

class Pgsql extends \Doctrine1\DataDict
{
    /**
     * @var string[] $reservedKeyWords     an array of reserved keywords by pgsql
     */
    protected static $reservedKeyWords = [
        'abort',
        'absolute',
        'access',
        'action',
        'add',
        'after',
        'aggregate',
        'all',
        'alter',
        'analyse',
        'analyze',
        'and',
        'any',
        'as',
        'asc',
        'assertion',
        'assignment',
        'at',
        'authorization',
        'backward',
        'before',
        'begin',
        'between',
        'bigint',
        'binary',
        'bit',
        'boolean',
        'both',
        'by',
        'cache',
        'called',
        'cascade',
        'case',
        'cast',
        'chain',
        'char',
        'character',
        'characteristics',
        'check',
        'checkpoint',
        'class',
        'close',
        'cluster',
        'coalesce',
        'collate',
        'column',
        'comment',
        'commit',
        'committed',
        'constraint',
        'constraints',
        'conversion',
        'convert',
        'copy',
        'create',
        'createdb',
        'createuser',
        'cross',
        'current_date',
        'current_time',
        'current_timestamp',
        'current_user',
        'cursor',
        'cycle',
        'database',
        'day',
        'deallocate',
        'dec',
        'decimal',
        'declare',
        'default',
        'deferrable',
        'deferred',
        'definer',
        'delete',
        'delimiter',
        'delimiters',
        'desc',
        'distinct',
        'do',
        'domain',
        'double',
        'drop',
        'each',
        'else',
        'encoding',
        'encrypted',
        'end',
        'escape',
        'except',
        'exclusive',
        'execute',
        'exists',
        'explain',
        'external',
        'extract',
        'false',
        'fetch',
        'float',
        'for',
        'force',
        'foreign',
        'forward',
        'freeze',
        'from',
        'full',
        'function',
        'get',
        'global',
        'grant',
        'group',
        'handler',
        'having',
        'hour',
        'ilike',
        'immediate',
        'immutable',
        'implicit',
        'in',
        'increment',
        'index',
        'inherits',
        'initially',
        'inner',
        'inout',
        'input',
        'insensitive',
        'insert',
        'instead',
        'int',
        'integer',
        'intersect',
        'interval',
        'into',
        'invoker',
        'is',
        'isnull',
        'isolation',
        'join',
        'key',
        'lancompiler',
        'language',
        'leading',
        'left',
        'level',
        'like',
        'limit',
        'listen',
        'load',
        'local',
        'localtime',
        'localtimestamp',
        'location',
        'lock',
        'match',
        'maxvalue',
        'minute',
        'minvalue',
        'mode',
        'month',
        'move',
        'names',
        'national',
        'natural',
        'nchar',
        'new',
        'next',
        'no',
        'nocreatedb',
        'nocreateuser',
        'none',
        'not',
        'nothing',
        'notify',
        'notnull',
        'null',
        'nullif',
        'numeric',
        'of',
        'off',
        'offset',
        'oids',
        'old',
        'on',
        'only',
        'operator',
        'option',
        'or',
        'order',
        'out',
        'outer',
        'overlaps',
        'overlay',
        'owner',
        'partial',
        'password',
        'path',
        'pendant',
        'placing',
        'position',
        'precision',
        'prepare',
        'primary',
        'prior',
        'privileges',
        'procedural',
        'procedure',
        'read',
        'real',
        'recheck',
        'references',
        'reindex',
        'relative',
        'rename',
        'replace',
        'reset',
        'restrict',
        'returns',
        'revoke',
        'right',
        'rollback',
        'row',
        'rule',
        'schema',
        'scroll',
        'second',
        'security',
        'select',
        'sequence',
        'serializable',
        'session',
        'session_user',
        'set',
        'setof',
        'share',
        'show',
        'similar',
        'simple',
        'smallint',
        'some',
        'stable',
        'start',
        'statement',
        'statistics',
        'stdin',
        'stdout',
        'storage',
        'strict',
        'substring',
        'sysid',
        'table',
        'temp',
        'template',
        'temporary',
        'then',
        'time',
        'timestamp',
        'to',
        'toast',
        'trailing',
        'transaction',
        'treat',
        'trigger',
        'trim',
        'true',
        'truncate',
        'trusted',
        'type',
        'unencrypted',
        'union',
        'unique',
        'unknown',
        'unlisten',
        'until',
        'update',
        'usage',
        'user',
        'using',
        'vacuum',
        'valid',
        'validator',
        'values',
        'varchar',
        'varying',
        'verbose',
        'version',
        'view',
        'volatile',
        'when',
        'where',
        'with',
        'without',
        'work',
        'write',
        'year',
        'zone',
    ];

    public function getNativeDeclaration(Column $field): string
    {
        $length   = $field->length;

        switch ($field->type) {
            case Type::Enum:
                $length ??= 255;
                // no break
            case Type::String:
            case Type::Array:
            case Type::Object:
                return $field->fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR(' . $this->conn->varchar_max_length . ')')
                    : ($length ? 'VARCHAR(' . $length . ')' : 'TEXT');

            case Type::BLOB:
                return 'BYTEA';
            case Type::Integer:
                if ($field->autoincrement) {
                    if ($length > 4) {
                        return 'BIGSERIAL';
                    }
                    return 'SERIAL';
                }
                if ($length <= 0) {
                    return 'INT';
                } elseif ($length <= 2) {
                    return 'SMALLINT';
                } elseif ($length == 3 || $length == 4) {
                    return 'INT';
                }
                return 'BIGINT';
            case Type::Inet:
                return 'INET';
            case Type::Bit:
                return 'VARBIT';
            case Type::Boolean:
                return 'BOOLEAN';
            case Type::Date:
                return 'DATE';
            case Type::Time:
                return 'TIME';
            case Type::DateTime:
            case Type::Timestamp:
                return 'TIMESTAMP';
            case Type::Float:
            case Type::Double:
                return 'FLOAT';
            case Type::Decimal:
                $length ??= 18;
                $scale  = $field->scale ?: $this->conn->getDecimalPlaces();
                return 'NUMERIC(' . $length . ',' . $scale . ')';
        }
        return $field->type->value . ($length !== null ? '(' . $length . ')' : null);
    }

    /**
     * Maps a native array description of a field to a portable Doctrine datatype and length
     *
     * @param array $field native field description
     *
     * @return array containing the various possible types, length, sign, fixed
     */
    public function getPortableDeclaration(array $field)
    {
        $length = (isset($field['length'])) ? $field['length'] : null;
        if ($length == '-1' && isset($field['atttypmod'])) {
            $length = $field['atttypmod'] - 4;
        }
        if ((int)$length <= 0) {
            $length = null;
        }
        $type     = [];
        $unsigned = $fixed = null;

        if (!isset($field['name'])) {
            $field['name'] = '';
        }

        $dbType = strtolower($field['type']);

        // Default from field for enum support
        $default  = isset($field['default']) ? $field['default'] : null;
        $enumName = null;
        if (strpos($dbType, 'enum') !== false) {
            $enumName = $dbType;
            $dbType   = 'enum';
        }

        switch ($dbType) {
            case 'inet':
                $type[] = 'inet';
                break;
            case 'bit':
            case 'varbit':
                $type[] = 'bit';
                break;
            case 'smallint':
            case 'int2':
                $unsigned = false;
                $length   = 2;
                if (preg_match('/^(is|has)/', $field['name'])) {
                    $type = ['boolean', 'integer'];
                } else {
                    $type = ['integer', 'boolean'];
                }
                break;
            case 'int':
            case 'int4':
            case 'integer':
            case 'serial':
            case 'serial4':
                $type[]   = 'integer';
                $unsigned = false;
                $length   = 4;
                break;
            case 'bigint':
            case 'int8':
            case 'bigserial':
            case 'serial8':
                $type[]   = 'integer';
                $unsigned = false;
                $length   = 8;
                break;
            case 'bool':
            case 'boolean':
                $type[] = 'boolean';
                $length = 1;
                break;
            case 'text':
            case 'varchar':
            case 'interval':
            case '_varchar':
                $fixed = false;
                // no break
            case 'tsvector':
            case 'unknown':
            case 'char':
            case 'character':
            case 'bpchar':
                $type[] = 'string';
                if ($length == '1') {
                    $type[] = 'boolean';
                    if (preg_match('/^(is|has)/', $field['name'])) {
                        $type = array_reverse($type);
                    }
                } elseif (strstr($dbType, 'text')) {
                    $type[] = 'clob';
                }
                if ($fixed !== false) {
                    $fixed = true;
                }
                break;
            case 'enum':
                $type[] = 'enum';
                $length = $length ? $length : 255;
                if ($default) {
                    $default = preg_replace('/\'(\w+)\'.*/', '$1', $default);
                }
                break;
            case 'date':
                $type[] = 'date';
                $length = null;
                break;
            case 'datetime':
            case 'timestamp':
            case 'timetz':
            case 'timestamptz':
                $type[] = 'timestamp';
                $length = null;
                break;
            case 'time':
                $type[] = 'time';
                $length = null;
                break;
            case 'float':
            case 'float4':
            case 'float8':
            case 'double':
            case 'double precision':
            case 'real':
                $type[] = 'float';
                break;
            case 'decimal':
            case 'money':
            case 'numeric':
                $type[] = 'decimal';
                break;
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'blob':
            case 'bytea':
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
            case 'oid':
                $type[] = 'blob';
                $type[] = 'clob';
                $length = null;
                break;
            case 'year':
                $type[] = 'integer';
                $type[] = 'date';
                $length = null;
                break;
            default:
                $type[] = $field['type'];
                $length = isset($field['length']) ? $field['length'] : null;
        }

        $ret = ['type'     => $type,
                     'length'   => $length,
                     'unsigned' => $unsigned,
                     'fixed'    => $fixed];

        // If this is postgresql enum type we will have non-null values here
        if ($default !== null) {
            $ret['default'] = $default;
        }
        if ($enumName !== null) {
            $ret['enumName'] = $enumName;
        }
        return $ret;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @return string DBMS specific SQL code portion that should be used to
     *       declare the specified field.
     */
    public function getIntegerDeclaration(Column $field): string
    {
        if ($field->autoincrement) {
            $name = $this->conn->quoteIdentifier($field->name, true);
            return $name . ' ' . $this->getNativeDeclaration($field);
        }

        $default = '';
        if ($field->hasDefault()) {
            $default = $field->default;
            if ($default === '') {
                $default = $field->notnull ? 0 : null;
            }

            $default = ' DEFAULT ' . ($default === null
                ? 'NULL'
                : $this->conn->quote($default, $field->type->value));
        }

        $notnull = $field->notnull ? ' NOT NULL' : '';

        $name = $this->conn->quoteIdentifier($field->name, true);
        return $name . ' ' . $this->getNativeDeclaration($field) . $default . $notnull;
    }

    /**
     * parseBoolean
     * parses a literal boolean value and returns
     * proper sql equivalent
     *
     * @param  string $value boolean value to be parsed
     * @return string           parsed boolean value
     */
    public function parseBoolean($value)
    {
        return $value;
    }
}
