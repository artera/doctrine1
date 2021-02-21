<?php
use MyCLabs\Enum\Enum;

/**
 * @extends Enum<int>
 */
class Doctrine_Record_State extends Enum
{
    private const DIRTY = 1;
    private const TDIRTY = 2;
    private const CLEAN = 3;
    private const PROXY = 4;
    private const TCLEAN = 5;
    private const LOCKED = 6;
    private const TLOCKED = 7;

    public function isDirty(): bool
    {
        return $this->equals(self::DIRTY()) || $this->equals(self::TDIRTY());
    }

    public function isClean(): bool
    {
        return $this->equals(self::CLEAN()) || $this->equals(self::TCLEAN());
    }

    public function isLocked(): bool
    {
        return $this->equals(self::LOCKED()) || $this->equals(self::TLOCKED());
    }

    public function isTransient(): bool
    {
        return $this->equals(self::TDIRTY()) || $this->equals(self::TCLEAN()) || $this->equals(self::TLOCKED());
    }

    public function lock(): self
    {
        return $this->isTransient() ? self::TLOCKED() : self::LOCKED();
    }

    public static function from($value): Doctrine_Record_State
    {
        /** @var Doctrine_Record_State */
        return parent::from($value);
    }
}
