<?php

namespace Doctrine1\Expression;

class Pgsql extends \Doctrine1\Expression\Driver
{
    /**
     * Returns the md5 sum of a field.
     *
     * Note: Not SQL92, but common functionality
     *
     * md5() works with the default PostgreSQL 8 versions.
     *
     * If you are using PostgreSQL 7.x or older you need
     * to make sure that the digest procedure is installed.
     * If you use RPMS (Redhat and Mandrake) install the postgresql-contrib
     * package. You must then install the procedure by running this shell command:
     * <code>
     * psql [dbname] < /usr/share/pgsql/contrib/pgcrypto.sql
     * </code>
     * You should make sure you run this as the postgres user.
     *
     * @param  string $column
     * @return string
     */
    public function md5($column)
    {
        $column = $this->getIdentifier($column);

        return 'MD5(' . $column . ')';
    }

    /**
     * Returns part of a string.
     *
     * Note: Not SQL92, but common functionality.
     *
     * @param  string $value the target $value the string or the string column.
     * @param  int    $from  extract from this characeter.
     * @param  int    $len   extract this amount of characters.
     * @return string sql that extracts part of a string.
     */
    public function substring($value, $from, $len = null)
    {
        $value = $this->getIdentifier($value);

        if ($len === null) {
            return 'SUBSTR(' . $value . ', ' . $from . ')';
        } else {
            return 'SUBSTR(' . $value . ', ' . $from . ', ' . $len . ')';
        }
    }

    /**
     * Returns a series of strings concatinated
     *
     * concat() accepts an arbitrary number of parameters. Each parameter
     * must contain an expression or an array with expressions.
     *
     * @param  string|array(string) strings that will be concatinated.
     * @return string
     */


    /**
     * PostgreSQLs AGE(<timestamp1> [, <timestamp2>]) function.
     *
     * @param  string $timestamp1 timestamp to subtract from NOW()
     * @param  string $timestamp2 optional; if given: subtract arguments
     * @return string
     */
    public function age($timestamp1, $timestamp2 = null)
    {
        if ($timestamp2 == null) {
            return 'AGE(' . $timestamp1 . ')';
        }
        return 'AGE(' . $timestamp1 . ', ' . $timestamp2 . ')';
    }

    /**
     * PostgreSQLs DATE_PART( <text>, <time> ) function.
     *
     * @param  string $text what to extract
     * @param  string $time timestamp or interval to extract from
     * @return string
     */
    public function date_part($text, $time)
    {
        return 'DATE_PART(' . $text . ', ' . $time . ')';
    }

    /**
     * PostgreSQLs TO_CHAR( <time>, <text> ) function.
     *
     * @param  string $time timestamp or interval
     * @param  string $text how to the format the output
     * @return string
     */
    public function to_char($time, $text)
    {
        return 'TO_CHAR(' . $time . ', ' . $text . ')';
    }

    /**
     * PostgreSQLs CONCAT() function
     *
     * @param  mixed ...$args values to concat
     * @return string
     */
    public function concat(...$args)
    {
        return join(' || ', $args);
    }

    /**
     * Returns the SQL string to return the current system date and time.
     *
     * @return string
     */
    public function now()
    {
        return 'LOCALTIMESTAMP(0)';
    }

    /**
     * regexp
     *
     * @return string           the regular expression operator
     */
    public function regexp()
    {
        return 'SIMILAR TO';
    }

    /**
     * return string to call a function to get random value inside an SQL statement
     *
     * @return string string to generate float between 0 and 1
     * @access public
     */
    public function random()
    {
        return 'RANDOM()';
    }

    /**
     * return syntax for pgsql TRANSLATE() dbms function
     *
     * @param  string $string
     * @param  string $from
     * @param  string $to
     * @return string $sql
     */
    public function translate($string, $from, $to)
    {
        $translate = 'TRANSLATE(' . $string . ', ' . $from . ', ' . $to . ')';
        return $translate;
    }

    /**
     * transform locate to position
     *
     * @param  string $substr string to find
     * @param  string $str    to find where
     * @return string
     */
    public function locate($substr, $str)
    {
        return $this->position($substr, $str);
    }

    /**
     * position
     *
     * @param  string $substr string to find
     * @param  string $str    to find where
     * @return string
     */
    public function position($substr, $str)
    {
        $substr = $this->getIdentifier($substr);
        $str    = $this->getIdentifier($str);

        return sprintf('POSITION(%s IN %s)', $substr, $str);
    }
}
