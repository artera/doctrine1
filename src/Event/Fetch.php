<?php

namespace Doctrine1\Event;

class Fetch extends \Doctrine1\Event
{
    public ?int $fetchMode = null;
    public ?int $cursorOrientation = null;
    public ?int $cursorOffset = null;
    public mixed $columnIndex = null;
    /** @phpstan-var mixed[]|null $data */
    public ?array $data = null;
}
