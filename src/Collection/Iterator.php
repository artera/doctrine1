<?php

namespace Doctrine1\Collection;

abstract class Iterator implements \Iterator
{
    protected \Doctrine1\Collection $collection;
    protected array $keys;
    protected mixed $key;
    protected int $index;
    protected int $count;

    public function __construct(\Doctrine1\Collection $collection)
    {
        $this->collection = $collection;
        $this->keys       = $this->collection->getKeys();
        $this->count      = $this->collection->count();
    }

    /**
     * rewinds the iterator
     */
    public function rewind(): void
    {
        $this->index = 0;
        $i           = $this->index;
        if (isset($this->keys[$i])) {
            $this->key = $this->keys[$i];
        }
    }

    /**
     * returns the current key
     */
    public function key(): int
    {
        return $this->key;
    }

    /**
     * returns the current record
     * @return \Doctrine1\Record
     */
    public function current(): mixed
    {
        return $this->collection->get($this->key);
    }

    /**
     * advances the internal pointer
     */
    public function next(): void
    {
        $this->index++;
        $i = $this->index;
        if (isset($this->keys[$i])) {
            $this->key = $this->keys[$i];
        }
    }
}
