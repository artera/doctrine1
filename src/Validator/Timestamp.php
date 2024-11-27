<?php

namespace Doctrine1\Validator;

use Laminas\Validator\AbstractValidator;

class Timestamp extends AbstractValidator
{
    public function isValid(mixed $value): bool
    {
        if ($value === null || $value instanceof \DateTimeInterface) {
            return true;
        }

        $splitChar = false !== strpos($value, 'T') ? 'T' : ' ';

        $e    = explode($splitChar, trim($value));
        $date = $e[0];
        $time = isset($e[1]) ? $e[1] : null;

        $dateValidator = new \Doctrine1\Validator\Date();
        if (!$dateValidator->isValid($date)) {
            return false;
        }

        $timeValidator = new \Doctrine1\Validator\Time();
        if (!$timeValidator->isValid($time)) {
            return false;
        }

        return true;
    }
}
