<?php

namespace Doctrine1\Connection\Mysql;

class Exception extends \Doctrine1\Connection\Exception
{
    /**
     * @var array $errorCodeMap         an array that is used for determining portable
     *                                  error code from a native database error code
     */
    protected static $errorCodeMap = [
                                      1004 => \Doctrine1\Core::ERR_CANNOT_CREATE,
                                      1005 => \Doctrine1\Core::ERR_CANNOT_CREATE,
                                      1006 => \Doctrine1\Core::ERR_CANNOT_CREATE,
                                      1007 => \Doctrine1\Core::ERR_ALREADY_EXISTS,
                                      1008 => \Doctrine1\Core::ERR_CANNOT_DROP,
                                      1022 => \Doctrine1\Core::ERR_ALREADY_EXISTS,
                                      1044 => \Doctrine1\Core::ERR_ACCESS_VIOLATION,
                                      1046 => \Doctrine1\Core::ERR_NODBSELECTED,
                                      1048 => \Doctrine1\Core::ERR_CONSTRAINT,
                                      1049 => \Doctrine1\Core::ERR_NOSUCHDB,
                                      1050 => \Doctrine1\Core::ERR_ALREADY_EXISTS,
                                      1051 => \Doctrine1\Core::ERR_NOSUCHTABLE,
                                      1054 => \Doctrine1\Core::ERR_NOSUCHFIELD,
                                      1061 => \Doctrine1\Core::ERR_ALREADY_EXISTS,
                                      1062 => \Doctrine1\Core::ERR_ALREADY_EXISTS,
                                      1064 => \Doctrine1\Core::ERR_SYNTAX,
                                      1091 => \Doctrine1\Core::ERR_NOT_FOUND,
                                      1100 => \Doctrine1\Core::ERR_NOT_LOCKED,
                                      1136 => \Doctrine1\Core::ERR_VALUE_COUNT_ON_ROW,
                                      1142 => \Doctrine1\Core::ERR_ACCESS_VIOLATION,
                                      1146 => \Doctrine1\Core::ERR_NOSUCHTABLE,
                                      1216 => \Doctrine1\Core::ERR_CONSTRAINT,
                                      1217 => \Doctrine1\Core::ERR_CONSTRAINT,
                                      1451 => \Doctrine1\Core::ERR_CONSTRAINT,
                                      1452 => \Doctrine1\Core::ERR_CONSTRAINT,
                                      ];

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
        $code = isset($errorInfo[1]) ? $errorInfo[1] : (isset($errorInfo[0]) ? $errorInfo[0] : null);
        if (isset(self::$errorCodeMap[$code])) {
            $this->portableCode = self::$errorCodeMap[$code];
            return true;
        }
        return false;
    }
}
