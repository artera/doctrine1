<?php

namespace Doctrine1\Connection;

class Exception extends \Doctrine1\Exception
{
    /**
     * @var array $errorMessages        an array containing messages for portable error codes
     */
    protected static $errorMessages = [
        \Doctrine1\Core::ERR                     => 'unknown error',
        \Doctrine1\Core::ERR_ALREADY_EXISTS      => 'already exists',
        \Doctrine1\Core::ERR_CANNOT_CREATE       => 'can not create',
        \Doctrine1\Core::ERR_CANNOT_ALTER        => 'can not alter',
        \Doctrine1\Core::ERR_CANNOT_REPLACE      => 'can not replace',
        \Doctrine1\Core::ERR_CANNOT_DELETE       => 'can not delete',
        \Doctrine1\Core::ERR_CANNOT_DROP         => 'can not drop',
        \Doctrine1\Core::ERR_CONSTRAINT          => 'constraint violation',
        \Doctrine1\Core::ERR_CONSTRAINT_NOT_NULL => 'null value violates not-null constraint',
        \Doctrine1\Core::ERR_DIVZERO             => 'division by zero',
        \Doctrine1\Core::ERR_INVALID             => 'invalid',
        \Doctrine1\Core::ERR_INVALID_DATE        => 'invalid date or time',
        \Doctrine1\Core::ERR_INVALID_NUMBER      => 'invalid number',
        \Doctrine1\Core::ERR_MISMATCH            => 'mismatch',
        \Doctrine1\Core::ERR_NODBSELECTED        => 'no database selected',
        \Doctrine1\Core::ERR_NOSUCHFIELD         => 'no such field',
        \Doctrine1\Core::ERR_NOSUCHTABLE         => 'no such table',
        \Doctrine1\Core::ERR_NOT_CAPABLE         => 'Doctrine backend not capable',
        \Doctrine1\Core::ERR_NOT_FOUND           => 'not found',
        \Doctrine1\Core::ERR_NOT_LOCKED          => 'not locked',
        \Doctrine1\Core::ERR_SYNTAX              => 'syntax error',
        \Doctrine1\Core::ERR_UNSUPPORTED         => 'not supported',
        \Doctrine1\Core::ERR_VALUE_COUNT_ON_ROW  => 'value count on row',
        \Doctrine1\Core::ERR_INVALID_DSN         => 'invalid DSN',
        \Doctrine1\Core::ERR_CONNECT_FAILED      => 'connect failed',
        \Doctrine1\Core::ERR_NEED_MORE_DATA      => 'insufficient data supplied',
        \Doctrine1\Core::ERR_NOSUCHDB            => 'no such database',
        \Doctrine1\Core::ERR_ACCESS_VIOLATION    => 'insufficient permissions',
        \Doctrine1\Core::ERR_LOADMODULE          => 'error while including on demand module',
        \Doctrine1\Core::ERR_TRUNCATED           => 'truncated',
        \Doctrine1\Core::ERR_DEADLOCK            => 'deadlock detected',
    ];

    /**
     * @see   \Doctrine1\Core::ERR_* constants
     * @since 1.0
     * @var   integer $portableCode           portable error code
     */
    protected $portableCode;

    /**
     * getPortableCode
     * returns portable error code
     *
     * @return integer      portable error code
     */
    public function getPortableCode()
    {
        return $this->portableCode;
    }

    /**
     * getPortableMessage
     * returns portable error message
     *
     * @return string       portable error message
     */
    public function getPortableMessage()
    {
        return $this->errorMessage($this->portableCode);
    }

    /**
     * Return a textual error message for a Doctrine error code
     *
     * @param int $value integer error code,
     *                   null to get the
     *                   current error
     *                   code-message map
     *
     * @return string  error message, or false if the error code was
     *                  not recognized
     */
    public function errorMessage($value = null)
    {
        return isset(self::$errorMessages[$value]) ?
           self::$errorMessages[$value] : self::$errorMessages[\Doctrine1\Core::ERR];
    }

    /**
     * This method checks if native error code/message can be
     * converted into a portable code and then adds this
     * portable error code to $portableCode field
     *
     * @param  array $errorInfo error info array
     * @since  1.0
     * @return boolean              whether or not the error info processing was successfull
     *                              (the process is successfull if portable error code was found)
     */
    public function processErrorInfo(array $errorInfo)
    {
        return true;
    }
}
