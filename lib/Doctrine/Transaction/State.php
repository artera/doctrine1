<?php
use MyCLabs\Enum\Enum;

class Doctrine_Transaction_State extends Enum
{
    private const SLEEP = 'open';
    private const ACTIVE = 'active';
    private const BUSY = 'busy';
}
