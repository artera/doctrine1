<?php

namespace Doctrine1\DataDict;

class Sqlite extends \Doctrine1\DataDict
{
    public function getNativeDeclaration(array $field)
    {
        if (!isset($field['type'])) {
            throw new \Doctrine1\DataDict\Exception('Missing column type.');
        }
        switch ($field['type']) {
            case 'enum':
                $field['length'] = isset($field['length']) && $field['length'] ? $field['length'] : 255;
                // no break
            case 'text':
            case 'object':
            case 'array':
            case 'string':
            case 'char':
            case 'gzip':
            case 'varchar':
                $length = (isset($field['length']) && $field['length']) ? $field['length'] : null;

                $fixed = ((isset($field['fixed']) && $field['fixed']) || $field['type'] == 'char') ? true : false;

                return $fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR(' . $this->conn->varchar_max_length . ')')
                    : ($length ? 'VARCHAR(' . $length . ')' : 'TEXT');
            case 'clob':
                if (!empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 255) {
                        return 'TINYTEXT';
                    } elseif ($length <= 65535) {
                        return 'TEXT';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMTEXT';
                    }
                }
                return 'LONGTEXT';
            case 'blob':
                if (!empty($field['length'])) {
                    $length = $field['length'];
                    if ($length <= 255) {
                        return 'TINYBLOB';
                    } elseif ($length <= 65535) {
                        return 'BLOB';
                    } elseif ($length <= 16777215) {
                        return 'MEDIUMBLOB';
                    }
                }
                return 'LONGBLOB';
            case 'integer':
            case 'boolean':
            case 'int':
                return 'INTEGER';
            case 'date':
                return 'DATE';
            case 'time':
                return 'TIME';
            case 'timestamp':
                return 'DATETIME';
            case 'float':
            case 'double':
                return 'DOUBLE';
            case 'decimal':
                $length = !empty($field['length']) ? $field['length'] : 18;
                $scale  = !empty($field['scale']) ? $field['scale'] : $this->conn->getDecimalPlaces();
                return 'DECIMAL(' . $length . ',' . $scale . ')';
        }
        return $field['type'] . (isset($field['length']) ? '(' . $field['length'] . ')' : null);
    }

    /**
     * Maps a native array description of a field to Doctrine datatype and length
     *
     * @param  array $field native field description
     * @return array containing the various possible types, length, sign, fixed
     */
    public function getPortableDeclaration(array $field)
    {
        $e             = explode('(', $field['type']);
        $field['type'] = $e[0];
        if (isset($e[1])) {
            $length          = trim($e[1], ')');
            $field['length'] = $length;
        }

        $dbType = strtolower($field['type']);

        if (!$dbType) {
            throw new \Doctrine1\DataDict\Exception('Missing "type" from field definition');
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
     * @param  string $name  name the field to be declared.
     * @param  array  $field associative array with the name of the properties
     *                       of the field being declared as array indexes.
     *                       Currently, the types of supported field
     *                       properties are as follows: unsigned Boolean flag
     *                       that indicates whether the field should be
     *                       declared as unsigned integer if possible. default
     *                       Integer value to be used as default for this
     *                       field. notnull Boolean flag that indicates
     *                       whether this field is constrained to not be set
     *                       to null.
     * @return string  DBMS specific SQL code portion that should be used to
     *                 declare the specified field.
     * @access protected
     */
    public function getIntegerDeclaration($name, array $field)
    {
        $default = $autoinc = '';
        $type    = $this->getNativeDeclaration($field);

        $autoincrement = isset($field['autoincrement']) && $field['autoincrement'];

        if ($autoincrement) {
            $autoinc = ' PRIMARY KEY AUTOINCREMENT';
            $type    = 'INTEGER';
        } elseif (array_key_exists('default', $field)) {
            if ($field['default'] === '') {
                $field['default'] = empty($field['notnull']) ? null : 0;
            }

            $default = ' DEFAULT ' . ($field['default'] === null
                ? 'NULL'
                : $this->conn->quote($field['default'], $field['type']));
        }/**
        elseif (empty($field['notnull'])) {
            $default = ' DEFAULT NULL';
        }
        */

        $notnull = (isset($field['notnull']) && $field['notnull']) ? ' NOT NULL' : '';

        // sqlite does not support unsigned attribute for autoinremented fields
        $unsigned = (isset($field['unsigned']) && $field['unsigned'] && !$autoincrement) ? ' UNSIGNED' : '';

        $name = $this->conn->quoteIdentifier($name, true);
        return $name . ' ' . $type . $unsigned . $default . $notnull . $autoinc;
    }
}