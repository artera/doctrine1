<?php

class Doctrine_Connection_Mysql_Exception extends Doctrine_Connection_Exception
{
    /**
     * @var array $errorCodeMap         an array that is used for determining portable
     *                                  error code from a native database error code
     */
    protected static $errorCodeMap = [
                                      1004 => Doctrine_Core::ERR_CANNOT_CREATE,
                                      1005 => Doctrine_Core::ERR_CANNOT_CREATE,
                                      1006 => Doctrine_Core::ERR_CANNOT_CREATE,
                                      1007 => Doctrine_Core::ERR_ALREADY_EXISTS,
                                      1008 => Doctrine_Core::ERR_CANNOT_DROP,
                                      1022 => Doctrine_Core::ERR_ALREADY_EXISTS,
                                      1044 => Doctrine_Core::ERR_ACCESS_VIOLATION,
                                      1046 => Doctrine_Core::ERR_NODBSELECTED,
                                      1048 => Doctrine_Core::ERR_CONSTRAINT,
                                      1049 => Doctrine_Core::ERR_NOSUCHDB,
                                      1050 => Doctrine_Core::ERR_ALREADY_EXISTS,
                                      1051 => Doctrine_Core::ERR_NOSUCHTABLE,
                                      1054 => Doctrine_Core::ERR_NOSUCHFIELD,
                                      1061 => Doctrine_Core::ERR_ALREADY_EXISTS,
                                      1062 => Doctrine_Core::ERR_ALREADY_EXISTS,
                                      1064 => Doctrine_Core::ERR_SYNTAX,
                                      1091 => Doctrine_Core::ERR_NOT_FOUND,
                                      1100 => Doctrine_Core::ERR_NOT_LOCKED,
                                      1136 => Doctrine_Core::ERR_VALUE_COUNT_ON_ROW,
                                      1142 => Doctrine_Core::ERR_ACCESS_VIOLATION,
                                      1146 => Doctrine_Core::ERR_NOSUCHTABLE,
                                      1216 => Doctrine_Core::ERR_CONSTRAINT,
                                      1217 => Doctrine_Core::ERR_CONSTRAINT,
                                      1451 => Doctrine_Core::ERR_CONSTRAINT,
                                      1452 => Doctrine_Core::ERR_CONSTRAINT,
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
