<?php

use Laminas\Validator\AbstractValidator;

class Doctrine_Validator_Notnull extends AbstractValidator
{
    const IS_NULL = 'isNull';

    /** @phpstan-var array<string, string> */
    protected $messageTemplates = [
        self::IS_NULL => 'The input must not be null',
    ];

    public function isValid($value)
    {
        if ($value === null) {
            $this->error(self::IS_NULL);
            return false;
        }
        return true;
    }
}
