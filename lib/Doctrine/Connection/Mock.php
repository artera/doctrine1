<?php

class Doctrine_Connection_Mock extends Doctrine_Connection_Common
{
    protected string $driverName = 'Mock';

    public function __construct(Doctrine_Manager $manager, PDO|array $adapter)
    {
    }
}
