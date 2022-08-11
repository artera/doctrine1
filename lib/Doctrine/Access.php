<?php

/**
 * @phpstan-template K
 * @phpstan-template V
 * @phpstan-implements ArrayAccess<K, V>
 */
abstract class Doctrine_Access implements ArrayAccess
{
    /**
     * Set an entire aray to the data
     *
     * @param  array $array An array of key => value pairs
     * @return Doctrine_Access
     */
    public function setArray(array $array)
    {
        foreach ($array as $k => $v) {
            $this->set($k, $v);
        }

        return $this;
    }

    /**
     * Set key and value to data
     *
     * @see    set, offsetSet
     * @param  mixed $value
     * @return void
     */
    public function __set(string $name, $value)
    {
        $this->set($name, $value);
    }

    /**
     * Get key from data
     *
     * @see    get, offsetGet
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * Check if key exists in data
     *
     * @return boolean whether or not this object contains $name
     */
    public function __isset(string $name)
    {
        return $this->contains($name);
    }

    /**
     * Remove key from data
     *
     * @param  scalar|null $name
     * @return void
     */
    public function __unset($name)
    {
        $this->remove($name);
    }

    /**
     * Check if an offset axists
     *
     * @param  scalar|null $offset
     * @return boolean Whether or not this object contains $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->contains($offset);
    }

    /**
     * An alias of get()
     *
     * @see    get, __get
     * @param  scalar|null $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        // array notation with no index was causing 'undefined variable: $offset' notices in php7,
        // for example:
        // $record->Relations[]->name = 'test';
        if (!isset($offset)) {
            return $this->get(null);
        }
        return $this->get($offset);
    }

    /**
     * Sets $offset to $value
     *
     * @see    set, __set
     * @param  scalar|null $offset
     * @param  mixed       $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!isset($offset)) {
            $this->add($value);
        } else {
            $this->set($offset, $value);
        }
    }

    /**
     * Unset a given offset
     *
     * @see    set, offsetSet, __set
     * @param  scalar|null $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->remove($offset);
    }

    /**
     * Remove the element with the specified offset
     *
     * @throws Doctrine_Exception
     * @param  scalar|null $offset The offset to remove
     * @return void
     */
    public function remove($offset)
    {
        throw new Doctrine_Exception('Remove is not supported for ' . get_class($this));
    }

    /**
     * Return the element with the specified offset
     *
     * @throws Doctrine_Exception
     * @param  scalar|null $offset The offset to return
     * @return mixed
     */
    public function get($offset)
    {
        throw new Doctrine_Exception('Get is not supported for ' . get_class($this));
    }

    /**
     * Set the offset to the value
     *
     * @throws Doctrine_Exception
     * @param scalar|null $offset The offset to set
     * @param mixed       $value  The value to set the offset to
     *
     * @return void
     */
    public function set($offset, $value)
    {
        throw new Doctrine_Exception('Set is not supported for ' . get_class($this));
    }

    /**
     * Check if the specified offset exists
     *
     * @throws Doctrine_Exception
     * @param  scalar|null $offset The offset to check
     * @return boolean True if exists otherwise false
     */
    public function contains($offset)
    {
        throw new Doctrine_Exception('Contains is not supported for ' . get_class($this));
    }

    /**
     * Add the value
     *
     * @throws Doctrine_Exception
     * @param  mixed $value The value to add
     * @return void
     */
    public function add($value): void
    {
        throw new Doctrine_Exception('Add is not supported for ' . get_class($this));
    }
}
