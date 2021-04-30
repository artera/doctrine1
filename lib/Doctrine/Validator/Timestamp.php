<?php

use Laminas\Validator\AbstractValidator;

class Doctrine_Validator_Timestamp extends AbstractValidator
{
    public function isValid($value)
    {
        if ($value === null) {
            return true;
        }

        $splitChar = false !== strpos($value, 'T') ? 'T' : ' ';

        $e    = explode($splitChar, trim($value));
        $date = isset($e[0]) ? $e[0] : null;
        $time = isset($e[1]) ? $e[1] : null;

        $dateValidator = new Doctrine_Validator_Date();
        if (!$dateValidator->isValid($date)) {
            return false;
        }

        $timeValidator = new Doctrine_Validator_Time();
        if (!$timeValidator->isValid($time)) {
            return false;
        }

        return true;
    }
}
