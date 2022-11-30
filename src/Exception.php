<?php

namespace Doctrine1;

class Exception extends \Exception
{
    /**
     * @var array $errorMessages       an array of error messages
     */
    protected static $errorMessages = [
        Core::ERR                     => 'unknown error',
        Core::ERR_ALREADY_EXISTS      => 'already exists',
        Core::ERR_CANNOT_CREATE       => 'can not create',
        Core::ERR_CANNOT_ALTER        => 'can not alter',
        Core::ERR_CANNOT_REPLACE      => 'can not replace',
        Core::ERR_CANNOT_DELETE       => 'can not delete',
        Core::ERR_CANNOT_DROP         => 'can not drop',
        Core::ERR_CONSTRAINT          => 'constraint violation',
        Core::ERR_CONSTRAINT_NOT_NULL => 'null value violates not-null constraint',
        Core::ERR_DIVZERO             => 'division by zero',
        Core::ERR_INVALID             => 'invalid',
        Core::ERR_INVALID_DATE        => 'invalid date or time',
        Core::ERR_INVALID_NUMBER      => 'invalid number',
        Core::ERR_MISMATCH            => 'mismatch',
        Core::ERR_NODBSELECTED        => 'no database selected',
        Core::ERR_NOSUCHFIELD         => 'no such field',
        Core::ERR_NOSUCHTABLE         => 'no such table',
        Core::ERR_NOT_CAPABLE         => 'Doctrine backend not capable',
        Core::ERR_NOT_FOUND           => 'not found',
        Core::ERR_NOT_LOCKED          => 'not locked',
        Core::ERR_SYNTAX              => 'syntax error',
        Core::ERR_UNSUPPORTED         => 'not supported',
        Core::ERR_VALUE_COUNT_ON_ROW  => 'value count on row',
        Core::ERR_INVALID_DSN         => 'invalid DSN',
        Core::ERR_CONNECT_FAILED      => 'connect failed',
        Core::ERR_NEED_MORE_DATA      => 'insufficient data supplied',
        Core::ERR_NOSUCHDB            => 'no such database',
        Core::ERR_ACCESS_VIOLATION    => 'insufficient permissions',
        Core::ERR_LOADMODULE          => 'error while including on demand module',
        Core::ERR_TRUNCATED           => 'truncated',
        Core::ERR_DEADLOCK            => 'deadlock detected',
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
           self::$errorMessages[$value] : self::$errorMessages[Core::ERR];
    }
}
