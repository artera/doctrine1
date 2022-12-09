<?php

namespace Doctrine1;

/**
 * @template Connection of Connection
 * @extends Connection\Module<Connection>
 */
abstract class DataDict extends Connection\Module
{
    /**
     * parseBoolean
     * parses a literal boolean value and returns
     * proper sql equivalent
     *
     * @param  string $value boolean value to be parsed
     * @return int|string           parsed boolean value
     */
    public function parseBoolean($value)
    {
        // parse booleans
        if ($value == 'true') {
            $value = 1;
        } elseif ($value == 'false') {
            $value = 0;
        }
        return $value;
    }

    /**
     * Obtain DBMS specific SQL code portion needed to declare an text type
     * field to be used in statements like CREATE TABLE.
     *
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     */
    abstract public function getNativeDeclaration(Column $field): string;
}
