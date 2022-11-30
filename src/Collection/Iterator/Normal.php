<?php

namespace Doctrine1\Collection\Iterator;

class Normal extends \Doctrine1\Collection\Iterator
{
    public function valid(): bool
    {
        return ($this->index < $this->count);
    }
}
