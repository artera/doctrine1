<?php

namespace Doctrine1\Connection\Pgsql;

class Exception extends \Doctrine1\Connection\Exception
{
    /**
     * @var array $errorRegexps         an array that is used for determining portable
     *                                  error code from a native database error message
     */
    protected static $errorRegexps = [
                                    '/parser: parse error at or near/i'
                                        => \Doctrine1\Core::ERR_SYNTAX,
                                    '/syntax error at/'
                                        => \Doctrine1\Core::ERR_SYNTAX,
                                    '/column reference .* is ambiguous/i'
                                        => \Doctrine1\Core::ERR_SYNTAX,
                                    '/column .* (of relation .*)?does not exist/i'
                                        => \Doctrine1\Core::ERR_NOSUCHFIELD,
                                    '/attribute .* not found|relation .* does not have attribute/i'
                                        => \Doctrine1\Core::ERR_NOSUCHFIELD,
                                    '/column .* specified in USING clause does not exist in (left|right) table/i'
                                        => \Doctrine1\Core::ERR_NOSUCHFIELD,
                                    '/(relation|sequence|table).*does not exist|class .* not found/i'
                                        => \Doctrine1\Core::ERR_NOSUCHTABLE,
                                    '/index .* does not exist/'
                                        => \Doctrine1\Core::ERR_NOT_FOUND,
                                    '/relation .* already exists/i'
                                        => \Doctrine1\Core::ERR_ALREADY_EXISTS,
                                    '/(divide|division) by zero$/i'
                                        => \Doctrine1\Core::ERR_DIVZERO,
                                    '/pg_atoi: error in .*: can\'t parse /i'
                                        => \Doctrine1\Core::ERR_INVALID_NUMBER,
                                    '/invalid input syntax for( type)? (integer|numeric)/i'
                                        => \Doctrine1\Core::ERR_INVALID_NUMBER,
                                    '/value .* is out of range for type \w*int/i'
                                        => \Doctrine1\Core::ERR_INVALID_NUMBER,
                                    '/integer out of range/i'
                                        => \Doctrine1\Core::ERR_INVALID_NUMBER,
                                    '/value too long for type character/i'
                                        => \Doctrine1\Core::ERR_INVALID,
                                    '/permission denied/'
                                        => \Doctrine1\Core::ERR_ACCESS_VIOLATION,
                                    '/violates [\w ]+ constraint/'
                                        => \Doctrine1\Core::ERR_CONSTRAINT,
                                    '/referential integrity violation/'
                                        => \Doctrine1\Core::ERR_CONSTRAINT,
                                    '/violates not-null constraint/'
                                        => \Doctrine1\Core::ERR_CONSTRAINT_NOT_NULL,
                                    '/more expressions than target columns/i'
                                        => \Doctrine1\Core::ERR_VALUE_COUNT_ON_ROW,
                                ];

    /**
     * This method checks if native error code/message can be
     * converted into a portable code and then adds this
     * portable error code to $portableCode field
     *
     * the portable error code is added at the end of array
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
