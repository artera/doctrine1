<?php

namespace Doctrine1;

final class None
{
    private static ?self $instance = null;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * @return false
     */
    public function exists(): bool
    {
        return false;
    }

    public function __toString(): string
    {
        return '';
    }
}
