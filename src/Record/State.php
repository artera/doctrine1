<?php

namespace Doctrine1\Record;

enum State: int
{
    case DIRTY = 1;
    case TDIRTY = 2;
    case CLEAN = 3;
    case PROXY = 4;
    case TCLEAN = 5;
    case LOCKED = 6;
    case TLOCKED = 7;

    public function isDirty(): bool
    {
        return $this === self::DIRTY || $this === self::TDIRTY;
    }

    public function isClean(): bool
    {
        return $this === self::CLEAN || $this === self::TCLEAN;
    }

    public function isLocked(): bool
    {
        return $this === self::LOCKED || $this === self::TLOCKED;
    }

    public function isTransient(): bool
    {
        return $this === self::TDIRTY || $this === self::TCLEAN || $this === self::TLOCKED;
    }

    public function lock(): self
    {
        return $this->isTransient() ? self::TLOCKED : self::LOCKED;
    }
}
