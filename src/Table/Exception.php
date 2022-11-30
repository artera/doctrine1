<?php

namespace Doctrine1\Table;

class Exception extends \Doctrine1\Exception
{
    /**
     * @param string $message
     */
    public function __construct($message = "Couldn't initialize table. One instance of this
                            table already exists. Always use \Doctrine1\Session::getTable(\$name)
                            to get on instance of a \Doctrine1\Table.")
    {
        parent::__construct($message);
    }
}
