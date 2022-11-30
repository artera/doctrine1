<?php

namespace Doctrine1;

abstract class Configurable
{
    /**
     * @var array $attributes               an array of containing all attributes
     */
    protected array $attributes = [];

    /**
     * @var ?Configurable $parent   the parent of this component
     */
    protected $parent;

    /**
     * sets a given attribute
     *
     * <code>
     * $manager->setAttribute(Core::ATTR_PORTABILITY, Core::PORTABILITY_ALL);
     * </code>
     *
     * @param  int|string $attribute either a Core::ATTR_* integer constant or a string
     *                          corresponding to a constant
     * @param  mixed $value     the value of the attribute
     * @see    Core::ATTR_* constants
     * @throws Exception           if the value is invalid
     *
     * @return $this
     */
    public function setAttribute(int|string $attribute, mixed $value): self
    {
        if (is_string($attribute)) {
            $attribute = (int) constant("Core::$attribute");
        }
        switch ($attribute) {
            case Core::ATTR_LISTENER:
                $this->setEventListener($value);
                break;
            case Core::ATTR_COLL_KEY:
                if (!($this instanceof Table)) {
                    throw new Exception('This attribute can only be set at table level.');
                }
                if ($value !== null && !$this->hasField($value)) {
                    throw new Exception("Couldn't set collection key attribute. No such field '$value'.");
                }
                break;
            case Core::ATTR_CACHE:
            case Core::ATTR_RESULT_CACHE:
            case Core::ATTR_QUERY_CACHE:
                if ($value !== null) {
                    if (!($value instanceof CacheInterface)) {
                        throw new Exception('Cache driver should implement CacheInterface');
                    }
                }
                break;
            case Core::ATTR_SEQCOL_NAME:
                if (!is_string($value)) {
                    throw new Exception('Sequence column name attribute only accepts string values');
                }
                break;
            case Core::ATTR_FIELD_CASE:
                if ($value != 0 && $value != CASE_LOWER && $value != CASE_UPPER) {
                    throw new Exception('Field case attribute should be either 0, CASE_LOWER or CASE_UPPER constant.');
                }
                break;
            case Core::ATTR_SEQNAME_FORMAT:
            case Core::ATTR_IDXNAME_FORMAT:
            case Core::ATTR_TBLNAME_FORMAT:
            case Core::ATTR_FKNAME_FORMAT:
                if ($this instanceof Table) {
                    throw new Exception(
                        'Sequence / index name format attributes cannot be set'
                                           . 'at table level (only at connection or global level).'
                    );
                }
                break;
        }

        $this->attributes[$attribute] = $value;
        return $this;
    }

    /**
     * @return $this
     */
    public function setEventListener(EventListener $listener): self
    {
        return $this->setListener($listener);
    }

    /**
     * @phpstan-param Record\ListenerInterface|Overloadable<Record\ListenerInterface> $listener
     * @return $this
     */
    public function addRecordListener(Record\ListenerInterface|Overloadable $listener, ?string $name = null): self
    {
        if (!isset($this->attributes[Core::ATTR_RECORD_LISTENER])
            || !($this->attributes[Core::ATTR_RECORD_LISTENER] instanceof Record\Listener\Chain)
        ) {
            $this->attributes[Core::ATTR_RECORD_LISTENER] = new Record\Listener\Chain();
        }
        $this->attributes[Core::ATTR_RECORD_LISTENER]->add($listener, $name);

        return $this;
    }

    /**
     * @phpstan-return Record\ListenerInterface|Overloadable<Record\ListenerInterface>
     * @throws EventListener\Exception
     */
    public function getRecordListener(): Record\ListenerInterface|Overloadable
    {
        if (!isset($this->attributes[Core::ATTR_RECORD_LISTENER])) {
            if (isset($this->parent)) {
                return $this->parent->getRecordListener();
            }
            throw new EventListener\Exception('Could not get a listener');
        }
        return $this->attributes[Core::ATTR_RECORD_LISTENER];
    }

    /**
     * @phpstan-param Record\ListenerInterface|Overloadable<Record\ListenerInterface> $listener
     * @return $this
     * @throws Exception
     */
    public function setRecordListener(Record\ListenerInterface|Overloadable $listener): self
    {
        // @phpstan-ignore-next-line
        if (!($listener instanceof Record\ListenerInterface)
            && !($listener instanceof Overloadable)
        ) {
            throw new Exception("Couldn't set eventlistener. Record listeners should implement either Record\ListenerInterface or Overloadable");
        }
        $this->attributes[Core::ATTR_RECORD_LISTENER] = $listener;

        return $this;
    }

    /**
     * @phpstan-param EventListenerInterface|Overloadable<EventListenerInterface> $listener
     * @return $this
     */
    public function addListener(EventListenerInterface|Overloadable $listener, ?string $name = null): self
    {
        if (!isset($this->attributes[Core::ATTR_LISTENER])
            || !($this->attributes[Core::ATTR_LISTENER] instanceof EventListener\Chain)
        ) {
            $this->attributes[Core::ATTR_LISTENER] = new EventListener\Chain();
        }
        $this->attributes[Core::ATTR_LISTENER]->add($listener, $name);

        return $this;
    }

    /**
     * @phpstan-return EventListenerInterface|Overloadable<EventListenerInterface>
     * @throws EventListener\Exception
     */
    public function getListener(): EventListenerInterface|Overloadable
    {
        if (!isset($this->attributes[Core::ATTR_LISTENER])) {
            if (isset($this->parent)) {
                return $this->parent->getListener();
            }
            throw new EventListener\Exception('Could not get a listener');
        }
        return $this->attributes[Core::ATTR_LISTENER];
    }

    /**
     * @phpstan-param EventListenerInterface|Overloadable<EventListenerInterface> $listener
     * @return $this
     * @throws EventListener\Exception
     */
    public function setListener(EventListenerInterface|Overloadable $listener): self
    {
        // @phpstan-ignore-next-line
        if (!$listener instanceof EventListenerInterface
            && !$listener instanceof Overloadable
        ) {
            throw new EventListener\Exception("Couldn't set eventlistener. EventListeners should implement either EventListenerInterface or Overloadable");
        }
        $this->attributes[Core::ATTR_LISTENER] = $listener;

        return $this;
    }

    /**
     * returns the value of an attribute
     */
    public function getAttribute(int $attribute): mixed
    {
        if (isset($this->attributes[$attribute])) {
            return $this->attributes[$attribute];
        }

        if (isset($this->parent)) {
            return $this->parent->getAttribute($attribute);
        }
        return null;
    }

    /**
     * Unset an attribute from this levels attributes
     */
    public function unsetAttribute(int $attribute): void
    {
        if (isset($this->attributes[$attribute])) {
            unset($this->attributes[$attribute]);
        }
    }

    /**
     * returns all attributes as an array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function setCharset(string $charset): void
    {
        $this->setAttribute(Core::ATTR_DEFAULT_TABLE_CHARSET, $charset);
    }

    public function getCharset(): mixed
    {
        return $this->getAttribute(Core::ATTR_DEFAULT_TABLE_CHARSET);
    }

    public function setCollate(string $collate): void
    {
        $this->setAttribute(Core::ATTR_DEFAULT_TABLE_COLLATE, $collate);
    }

    public function getCollate(): mixed
    {
        return $this->getAttribute(Core::ATTR_DEFAULT_TABLE_COLLATE);
    }

    /**
     * sets a parent for this configurable component
     * the parent must be configurable component itself
     */
    public function setParent(Configurable $component): void
    {
        $this->parent = $component;
    }

    /**
     * returns the parent of this component
     *
     * @return mixed
     */
    public function getParent()
    {
        return $this->parent;
    }
}
