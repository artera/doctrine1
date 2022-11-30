<?php

namespace Doctrine1\Expression;

class Sqlite extends \Doctrine1\Expression\Driver
{
    /**
     * Returns the md5 sum of the data that SQLite's md5() function receives.
     *
     * @param  mixed $data
     * @return string
     */
    public static function md5Impl($data)
    {
        return md5($data);
    }

    /**
     * Returns the modules of the data that SQLite's mod() function receives.
     *
     * @param  integer $dividend
     * @param  integer $divisor
     * @return int
     */
    public static function modImpl($dividend, $divisor)
    {
        return $dividend % $divisor;
    }

    /**
     * Returns a concatenation of the data that SQLite's concat() function receives.
     *
     * @return string
     */
    public static function concatImpl()
    {
        $args = func_get_args();
        return join('', $args);
    }

    /**
     * locate
     * returns the position of the first occurrence of substring $substr in string $str that
     * SQLite's locate() function receives
     *
     * @param  string $substr literal string to find
     * @param  string $str    literal string
     */
    public static function locateImpl(string $substr, string $str): ?int
    {
        $res = strpos($str, $substr);
        return $res === false ? null : $res;
    }

    public static function sha1Impl(string $str): string
    {
        return sha1($str);
    }

    public static function ltrimImpl(string $str): string
    {
        return ltrim($str);
    }

    public static function rtrimImpl(string $str): string
    {
        return rtrim($str);
    }

    public static function trimImpl(string $str): string
    {
        return trim($str);
    }

    public static function nowImpl(): string
    {
        return date('Y-m-d h:i:s');
    }

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
     * soundex
     * Returns a string to call a function to compute the
     * soundex encoding of a string
     *
     * The string "?000" is returned if the argument is NULL.
     *
     * @param  string $value
     * @return string   SQL soundex function with given parameter
     */
    public function soundex($value)
    {
        return 'SOUNDEX(' . $value . ')';
    }

    /**
     * Return string to call a variable with the current timestamp inside an SQL statement
     * There are three special variables for current date and time.
     *
     * @param  string $type
     * @return string       sqlite function as string
     */
    public function now($type = 'timestamp')
    {
        switch ($type) {
            case 'time':
                return 'time(\'now\')';
            case 'date':
                return 'date(\'now\')';
            case 'timestamp':
            default:
                return 'datetime(\'now\')';
        }
    }

    /**
     * return string to call a function to get random value inside an SQL statement
     *
     * @return string to generate float between 0 and 1
     */
    public function random()
    {
        return '((RANDOM() + 2147483648) / 4294967296)';
    }

    /**
     * return string to call a function to get a substring inside an SQL statement
     *
     * Note: Not SQL92, but common functionality.
     *
     * SQLite only supports the 2 parameter variant of this function
     *
     * @param  string  $value    an sql string literal or column name/alias
     * @param  integer $position where to start the substring portion
     * @param  integer $length   the substring portion length
     * @return string               SQL substring function with given parameters
     */
    public function substring($value, $position, $length = null)
    {
        if ($length !== null) {
            return 'SUBSTR(' . $value . ', ' . $position . ', ' . $length . ')';
        }
        return 'SUBSTR(' . $value . ', ' . $position . ', LENGTH(' . $value . '))';
    }
}
