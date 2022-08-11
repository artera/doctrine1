<?php

use Laminas\Validator\AbstractValidator;

class Doctrine_Validator_Json extends AbstractValidator
{
    public function isValid(mixed $value): bool
    {
        json_decode($value);
        $code = json_last_error();
        if ($code !== JSON_ERROR_NONE) {
            /** @phpstan-ignore-next-line */
            $this->abstractOptions['messages'][(string) $code] = json_last_error_msg();
            return false;
        }
        return true;
    }
}
