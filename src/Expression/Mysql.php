<?php

namespace Doctrine1\Expression;

class Mysql extends \Doctrine1\Expression\Driver
{
    /**
     * returns the regular expression operator
     *
     * @return string
     */
    public function regexp()
    {
        return 'RLIKE';
    }

    /**
     * return string to call a function to get random value inside an SQL statement
     *
     * @return string to generate float between 0 and 1
     */
    public function random()
    {
        return 'RAND()';
    }

    /**
     * Returns global unique identifier
     *
     * @return string to get global unique identifier
     */
    public function guid()
    {
        return 'UUID()';
    }

    /**
     * Returns the year from dbms
     *
     * @param  string $column
     * @return string to get year from dbms
     */
    public function year($column)
    {
        $column = $this->getIdentifier($column);
        return 'YEAR(' . $column . ')';
    }

    /**
     * Returns the month from dbms
     *
     * @param  string $column
     * @return string to get month from dbms
     */
    public function month($column)
    {
        $column = $this->getIdentifier($column);
        return 'MONTH(' . $column . ')';
    }

    /**
     * Returns day from dbms
     *
     * @param  string $column
     * @return string to get day from dbms
     */
    public function day($column)
    {
        $column = $this->getIdentifier($column);
        return 'DAY(' . $column . ')';
    }

    /**
     * Returns soundex from dbms
     *
     * @param  string $column
     * @return string to get soundex from dbms
     */
    public function soundex($column)
    {
        return 'SOUNDEX(' . $column . ')';
    }
}
