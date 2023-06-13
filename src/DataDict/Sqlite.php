<?php

namespace Doctrine1\DataDict;

use Doctrine1\Column;
use Doctrine1\Column\Type;

class Sqlite extends \Doctrine1\DataDict
{
    public function getNativeDeclaration(Column $field): string
    {
        $length   = $field->length;

        switch ($field->type) {
            case Type::Enum:
                $length ??= 255;
                // no break
            case Type::Text:
            case Type::String:
            case Type::Object:
            case Type::Array:
                return $field->fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR(' . $this->conn->varchar_max_length . ')')
                    : ($length ? 'VARCHAR(' . $length . ')' : 'TEXT');
            case Type::BLOB:
                if (!empty($length)) {
                    if ($length <= 255) {
                        return 'TINYBLOB';
                    } elseif ($length <= 65535) {
                        return 'BLOB';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMBLOB';
                    }
                }
                return 'LONGBLOB';
            case Type::Integer:
            case Type::Boolean:
                return 'INTEGER';
            case Type::Date:
                return 'DATE';
            case Type::Time:
                return 'TIME';
            case Type::DateTime:
            case Type::Timestamp:
                return 'DATETIME';
            case Type::Float:
            case Type::Double:
                return 'DOUBLE';
            case Type::Decimal:
                $length ??= 18;
                $scale  = $field->scale ?: $this->conn->getDecimalPlaces();
                return 'DECIMAL(' . $length . ',' . $scale . ')';
        }
        return $field->type->value . ($length !== null ? '(' . $length . ')' : null);
    }

    /**
     * Maps a native array description of a field to Doctrine datatype and length
     *
     * @param  array $field native field description
     * @return array containing the various possible types, length, sign, fixed
     */
    public function getPortableDeclaration(array $field)
    {
        $e = explode('(', $field['type']);
        $field['type'] = $e[0];
        if (isset($e[1])) {
            $length          = trim($e[1], ')');
            $field['length'] = $length;
        }

        $dbType = strtolower($field['type']);

        if (!$dbType) {
            throw new Exception('Missing "type" from field definition');
        }

        $length   = $field['length'] ?? null;
        $unsigned = $field['unsigned'] ?? null;
        $fixed    = null;
        $type     = [];

        if (!isset($field['name'])) {
            $field['name'] = '';
        }

        switch ($dbType) {
            case 'boolean':
                $type[] = 'boolean';
                break;
            case 'tinyint':
                $type[] = 'integer';
                $type[] = 'boolean';
                if (preg_match('/^(is|has)/', $field['name'])) {
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
            case 'serial':
                $type[]   = 'integer';
                $unsigned = (bool) preg_match('/ unsigned/i', $field['type']);
                $length   = 4;
                break;
            case 'bigint':
            case 'bigserial':
                $type[]   = 'integer';
                $unsigned = (bool) preg_match('/ unsigned/i', $field['type']);
                $length   = 8;
                break;
            case 'clob':
            case 'tinytext':
            case 'mediumtext':
            case 'longtext':
            case 'text':
            case 'varchar':
            case 'varchar2':
            case 'nvarchar':
            case 'ntext':
            case 'image':
            case 'nchar':
                $fixed = false;
                // no break
            case 'char':
                $type[] = 'text';
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
                $type[] = 'float';
                $length = null;
                break;
            case 'decimal':
            case 'numeric':
                $type[] = 'decimal';
                $length = null;
                break;
            case 'tinyblob':
            case 'mediumblob':
            case 'longblob':
            case 'blob':
                $type[] = 'blob';
                $length = null;
                break;
            case 'year':
                $type[] = 'integer';
                $type[] = 'date';
                $length = null;
                break;
            default:
                $type[] = $field['type'];
        }

        return [
            'type'     => $type,
            'length'   => $length,
            'unsigned' => $unsigned,
            'fixed'    => $fixed,
        ];
    }

    /**
     * Obtain DBMS specific SQL code portion needed to declare an integer type
     * field to be used in statements like CREATE TABLE.
     *
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access protected
     */
    public function getIntegerDeclaration(Column $field): string
    {
        $default = $autoinc = '';
        $type    = $this->getNativeDeclaration($field);

        if ($field->autoincrement) {
            $autoinc = ' PRIMARY KEY AUTOINCREMENT';
            $type    = 'INTEGER';
        } elseif ($field->hasDefault()) {
            $default = $field->default;
            if ($default === '') {
                $default = $field->notnull ? 0 : null;
            }

            $default = ' DEFAULT ' . ($default === null
                ? 'NULL'
                : $this->conn->quote($default, $field->type->value));
        }

        $notnull = $field->notnull ? ' NOT NULL' : '';

        // sqlite does not support unsigned attribute for autoinremented fields
        $unsigned = $field->unsigned && !$field->autoincrement ? ' UNSIGNED' : '';

        $name = $this->conn->quoteIdentifier($field->name, true);
        return $name . ' ' . $type . $unsigned . $default . $notnull . $autoinc;
    }
}
