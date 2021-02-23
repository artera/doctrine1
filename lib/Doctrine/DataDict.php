<?php

/**
 * @template Connection of Doctrine_Connection
 * @extends Doctrine_Connection_Module<Connection>
 */
abstract class Doctrine_DataDict extends Doctrine_Connection_Module
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
     * @param array $field associative array with the name of the properties
     *                     of the field being declared as array indexes.
     *                     Currently, the types of supported field
     *                     properties are as follows: length Integer value
     *                     that determines the maximum length of the text
     *                     field. If this argument is missing the field
     *                     should be declared to have the longest length
     *                     allowed by the DBMS. default Text value to be
     *                     used as default for this field. notnull Boolean
     *                     flag that indicates whether this field is
     *                     constrained to not be set to null.
     *
     * @return string  DBMS specific SQL code portion that should be used to
     *      declare the specified field.
     */
    abstract public function getNativeDeclaration(array $field);
}
