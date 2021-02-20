<?php

/**
 * @template Connection of Doctrine_Connection
 * @extends Doctrine_Connection_Module<Connection>
 */
class Doctrine_DataDict extends Doctrine_Connection_Module
{
    /**
     * parseBoolean
     * parses a literal boolean value and returns
     * proper sql equivalent
     *
     * @param  string $value boolean value to be parsed
     * @return int|string           parsed boolean value
     */
    public function parseBoolean($value)
    {
        // parse booleans
        if ($value == 'true') {
            $value = 1;
        } elseif ($value == 'false') {
            $value = 0;
        }
        return $value;
    }
}
