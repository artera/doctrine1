<?php

namespace Doctrine1\Connection\Sqlite;

class Exception extends \Doctrine1\Connection\Exception
{
    /**
     * @var array $errorRegexps         an array that is used for determining portable
     *                                  error code from a native database error message
     */
    protected static $errorRegexps = [
                              '/^no such table:/'                    => \Doctrine1\Core::ERR_NOSUCHTABLE,
                              '/^no such index:/'                    => \Doctrine1\Core::ERR_NOT_FOUND,
                              '/^(table|index) .* already exists$/'  => \Doctrine1\Core::ERR_ALREADY_EXISTS,
                              '/PRIMARY KEY must be unique/i'        => \Doctrine1\Core::ERR_CONSTRAINT,
                              '/is not unique/'                      => \Doctrine1\Core::ERR_CONSTRAINT,
                              '/columns .* are not unique/i'         => \Doctrine1\Core::ERR_CONSTRAINT,
                              '/uniqueness constraint failed/'       => \Doctrine1\Core::ERR_CONSTRAINT,
                              '/may not be NULL/'                    => \Doctrine1\Core::ERR_CONSTRAINT_NOT_NULL,
                              '/^no such column:/'                   => \Doctrine1\Core::ERR_NOSUCHFIELD,
                              '/column not present in both tables/i' => \Doctrine1\Core::ERR_NOSUCHFIELD,
                              '/^near ".*": syntax error$/'          => \Doctrine1\Core::ERR_SYNTAX,
                              '/[0-9]+ values for [0-9]+ columns/i'  => \Doctrine1\Core::ERR_VALUE_COUNT_ON_ROW,
                              ];

    /**
     * This method checks if native error code/message can be
     * converted into a portable code and then adds this
     * portable error code to $portableCode field
     *
     * @param  array $errorInfo error info array
     * @since  1.0
     * @see    \Doctrine1\Core::ERR_* constants
     * @see    \Doctrine1\Connection::$portableCode
     * @return boolean              whether or not the error info processing was successfull
     *                              (the process is successfull if portable error code was found)
     */
    public function processErrorInfo(array $errorInfo)
    {
        foreach (self::$errorRegexps as $regexp => $code) {
            if (preg_match($regexp, $errorInfo[2])) {
                $this->portableCode = $code;
                return true;
            }
        }
        return false;
    }
}
