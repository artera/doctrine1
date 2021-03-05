<?php
use MyCLabs\Enum\Enum;

/**
 * @extends Enum<int>
 */
class Doctrine_Query_Type extends Enum
{
    private const SELECT = 0;
    private const DELETE = 1;
    private const UPDATE = 2;
    private const INSERT = 3;
    private const CREATE = 4;

    public function isSelect(): bool
    {
        return $this->getValue() === self::SELECT;
    }

    public function isDelete(): bool
    {
        return $this->getValue() === self::DELETE;
    }

    public function isUpdate(): bool
    {
        return $this->getValue() === self::UPDATE;
    }

    public function isInsert(): bool
    {
        return $this->getValue() === self::INSERT;
    }

    public function isCreate(): bool
    {
        return $this->getValue() === self::CREATE;
    }

    public static function from($value): Doctrine_Query_Type
    {
        /** @var Doctrine_Query_Type */
        return parent::from($value);
    }
}
