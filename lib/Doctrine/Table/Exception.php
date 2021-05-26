<?php

class Doctrine_Table_Exception extends Doctrine_Exception
{
    /**
     * @param string $message
     */
    public function __construct($message = "Couldn't initialize table. One instance of this
                            table already exists. Always use Doctrine_Session::getTable(\$name)
                            to get on instance of a Doctrine_Table.")
    {
        parent::__construct($message);
    }
}
