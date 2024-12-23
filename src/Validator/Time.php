<?php

namespace Doctrine1\Validator;

use Laminas\Validator\AbstractValidator;

class Time extends AbstractValidator
{
    public function isValid(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (!preg_match('/^\s*(\d{2}):(\d{2})(:(\d{2}))?(\.(\d{1,6}))?([+-]\d{1,2}(:(\d{2}))?)?\s*$/', $value, $matches)) {
            return false;
        }

        $hh    = intval($matches[1]);
        $mm    = intval($matches[2]);
        $ss    = (isset($matches[4])) ? intval($matches[4]) : 0;
        $ms    = (isset($matches[6])) ? intval($matches[6]) : 0;
        $tz_hh = (isset($matches[7])) ? intval($matches[7]) : 0;
        $tz_mm = (isset($matches[9])) ? intval($matches[9]) : 0;

        return ($hh >= 0 && $hh <= 23) &&
               ($mm >= 0 && $mm <= 59) &&
               ($ss >= 0 && $ss <= 59) &&
               ($tz_hh >= -13 && $tz_hh <= 14) &&
               ($tz_mm >= 0 && $tz_mm <= 59);
    }
}
