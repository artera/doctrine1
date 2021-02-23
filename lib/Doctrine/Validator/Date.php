<?php

use Laminas\Validator\AbstractValidator;

class Doctrine_Validator_Date extends AbstractValidator
{
    public function isValid($value)
    {
        if (is_null($value)) {
            return true;
        }
        $e = explode('-', $value);

        if (count($e) !== 3) {
            return false;
        }
        $e2   = explode(' ', $e[2]);
        $e[2] = $e2[0];
        return checkdate((int) $e[1], (int) $e[2], (int) $e[0]);
    }
}
