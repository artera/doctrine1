<?php

namespace Doctrine1\Validator;

use Laminas\Validator\AbstractValidator;

class Notnull extends AbstractValidator
{
    public const IS_NULL = 'isNull';

    /** @phpstan-var array<string, string> */
    protected array $messageTemplates = [
        self::IS_NULL => 'The input must not be null',
    ];

    public function isValid(mixed $value): bool
    {
        if ($value === null) {
            $this->error(self::IS_NULL);
            return false;
        }
        return true;
    }
}
