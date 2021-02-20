<?php

/**
 * @template T
 * @mixin T
 */
interface Doctrine_Overloadable
{
    /**
     * __call
     * method overloader
     *
     * @param  string $m the name of the method
     * @param  array  $a method arguments
     * @return mixed        return value of the method
     */
    public function __call($m, $a);
}
