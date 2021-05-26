<?php

class Doctrine_Exception extends Exception
{
    /**
     * @var array $errorMessages       an array of error messages
     */
    protected static $errorMessages = [
        Doctrine_Core::ERR                     => 'unknown error',
        Doctrine_Core::ERR_ALREADY_EXISTS      => 'already exists',
        Doctrine_Core::ERR_CANNOT_CREATE       => 'can not create',
        Doctrine_Core::ERR_CANNOT_ALTER        => 'can not alter',
        Doctrine_Core::ERR_CANNOT_REPLACE      => 'can not replace',
        Doctrine_Core::ERR_CANNOT_DELETE       => 'can not delete',
        Doctrine_Core::ERR_CANNOT_DROP         => 'can not drop',
        Doctrine_Core::ERR_CONSTRAINT          => 'constraint violation',
        Doctrine_Core::ERR_CONSTRAINT_NOT_NULL => 'null value violates not-null constraint',
        Doctrine_Core::ERR_DIVZERO             => 'division by zero',
        Doctrine_Core::ERR_INVALID             => 'invalid',
        Doctrine_Core::ERR_INVALID_DATE        => 'invalid date or time',
        Doctrine_Core::ERR_INVALID_NUMBER      => 'invalid number',
        Doctrine_Core::ERR_MISMATCH            => 'mismatch',
        Doctrine_Core::ERR_NODBSELECTED        => 'no database selected',
        Doctrine_Core::ERR_NOSUCHFIELD         => 'no such field',
        Doctrine_Core::ERR_NOSUCHTABLE         => 'no such table',
        Doctrine_Core::ERR_NOT_CAPABLE         => 'Doctrine backend not capable',
        Doctrine_Core::ERR_NOT_FOUND           => 'not found',
        Doctrine_Core::ERR_NOT_LOCKED          => 'not locked',
        Doctrine_Core::ERR_SYNTAX              => 'syntax error',
        Doctrine_Core::ERR_UNSUPPORTED         => 'not supported',
        Doctrine_Core::ERR_VALUE_COUNT_ON_ROW  => 'value count on row',
        Doctrine_Core::ERR_INVALID_DSN         => 'invalid DSN',
        Doctrine_Core::ERR_CONNECT_FAILED      => 'connect failed',
        Doctrine_Core::ERR_NEED_MORE_DATA      => 'insufficient data supplied',
        Doctrine_Core::ERR_NOSUCHDB            => 'no such database',
        Doctrine_Core::ERR_ACCESS_VIOLATION    => 'insufficient permissions',
        Doctrine_Core::ERR_LOADMODULE          => 'error while including on demand module',
        Doctrine_Core::ERR_TRUNCATED           => 'truncated',
        Doctrine_Core::ERR_DEADLOCK            => 'deadlock detected',
    ];

    /**
     * Return a textual error message for a Doctrine error code
     *
     * @param int $value integer error code,
     *                   null to get the current error code-message map,
     *                   or an array with a new error code-message map
     *
     * @return string|array  error message
     */
    public function errorMessage($value = null)
    {
        if ($value === null) {
            return self::$errorMessages;
        }

        return isset(self::$errorMessages[$value]) ?
           self::$errorMessages[$value] : self::$errorMessages[Doctrine_Core::ERR];
    }
}
