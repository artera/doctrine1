<?php

namespace Doctrine1\Validator;

use Laminas\Validator\AbstractValidator;

class Json extends AbstractValidator
{
    public function isValid(mixed $value): bool
    {
        if (function_exists('json_validate')) {
            json_validate($value);
        } else {
            json_decode($value);
        }
        $code = json_last_error();
        if ($code !== JSON_ERROR_NONE) {
            /** @phpstan-ignore-next-line */
            $this->errorMessages[(string) $code] = json_last_error_msg();
            return false;
        }
        return true;
    }
}
