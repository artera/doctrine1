<?php

class Doctrine_Event_Fetch extends Doctrine_Event
{
    public ?int $fetchMode = null;
    public ?int $cursorOrientation = null;
    public ?int $cursorOffset = null;
    public mixed $columnIndex = null;
    /** @phpstan-var mixed[]|null $data */
    public ?array $data = null;
}
