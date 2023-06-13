<?php

namespace Doctrine1\Validator;

use Laminas\Validator\AbstractValidator;

class Date extends AbstractValidator
{
    public function isValid($value)
    {
        if ($value === null || $value instanceof \DateTimeInterface) {
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
