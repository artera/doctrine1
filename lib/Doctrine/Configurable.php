<?php

abstract class Doctrine_Configurable
{
    /**
     * @var array $attributes               an array of containing all attributes
     */
    protected array $attributes = [];

    /**
     * @var Doctrine_Configurable $parent   the parent of this component
     */
    protected $parent;

    /**
     * sets a given attribute
     *
     * <code>
     * $manager->setAttribute(Doctrine_Core::ATTR_PORTABILITY, Doctrine_Core::PORTABILITY_ALL);
     * </code>
     *
     * @param  int|string $attribute either a Doctrine_Core::ATTR_* integer constant or a string
     *                          corresponding to a constant
     * @param  mixed $value     the value of the attribute
     * @see    Doctrine_Core::ATTR_* constants
     * @throws Doctrine_Exception           if the value is invalid
     *
     * @return $this
     */
    public function setAttribute(int|string $attribute, mixed $value): self
    {
        if (is_string($attribute)) {
            $attribute = (int) constant("Doctrine_Core::$attribute");
        }
        switch ($attribute) {
            case Doctrine_Core::ATTR_LISTENER:
                $this->setEventListener($value);
                break;
            case Doctrine_Core::ATTR_COLL_KEY:
                if (!($this instanceof Doctrine_Table)) {
                    throw new Doctrine_Exception('This attribute can only be set at table level.');
                }
                if ($value !== null && !$this->hasField($value)) {
                    throw new Doctrine_Exception("Couldn't set collection key attribute. No such field '$value'.");
                }
                break;
            case Doctrine_Core::ATTR_CACHE:
            case Doctrine_Core::ATTR_RESULT_CACHE:
            case Doctrine_Core::ATTR_QUERY_CACHE:
                if ($value !== null) {
                    if (!($value instanceof Doctrine_Cache_Interface)) {
                        throw new Doctrine_Exception('Cache driver should implement Doctrine_Cache_Interface');
                    }
                }
                break;
            case Doctrine_Core::ATTR_SEQCOL_NAME:
                if (!is_string($value)) {
                    throw new Doctrine_Exception('Sequence column name attribute only accepts string values');
                }
                break;
            case Doctrine_Core::ATTR_FIELD_CASE:
                if ($value != 0 && $value != CASE_LOWER && $value != CASE_UPPER) {
                    throw new Doctrine_Exception('Field case attribute should be either 0, CASE_LOWER or CASE_UPPER constant.');
                }
                break;
            case Doctrine_Core::ATTR_SEQNAME_FORMAT:
            case Doctrine_Core::ATTR_IDXNAME_FORMAT:
            case Doctrine_Core::ATTR_TBLNAME_FORMAT:
            case Doctrine_Core::ATTR_FKNAME_FORMAT:
                if ($this instanceof Doctrine_Table) {
                    throw new Doctrine_Exception(
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
    public function setEventListener(Doctrine_EventListener $listener): self
    {
        return $this->setListener($listener);
    }

    /**
     * @phpstan-param Doctrine_Record_Listener_Interface|Doctrine_Overloadable<Doctrine_Record_Listener_Interface> $listener
     * @return $this
     */
    public function addRecordListener(Doctrine_Record_Listener_Interface|Doctrine_Overloadable $listener, ?string $name = null): self
    {
        if (!isset($this->attributes[Doctrine_Core::ATTR_RECORD_LISTENER])
            || !($this->attributes[Doctrine_Core::ATTR_RECORD_LISTENER] instanceof Doctrine_Record_Listener_Chain)
        ) {
            $this->attributes[Doctrine_Core::ATTR_RECORD_LISTENER] = new Doctrine_Record_Listener_Chain();
        }
        $this->attributes[Doctrine_Core::ATTR_RECORD_LISTENER]->add($listener, $name);

        return $this;
    }

    /**
     * @phpstan-return Doctrine_Record_Listener_Interface|Doctrine_Overloadable<Doctrine_Record_Listener_Interface>
     */
    public function getRecordListener(): Doctrine_Record_Listener_Interface|Doctrine_Overloadable
    {
        if (!isset($this->attributes[Doctrine_Core::ATTR_RECORD_LISTENER])) {
            if (isset($this->parent)) {
                return $this->parent->getRecordListener();
            }
            throw new Doctrine_EventListener_Exception('Could not get a listener');
        }
        return $this->attributes[Doctrine_Core::ATTR_RECORD_LISTENER];
    }

    /**
     * @phpstan-param Doctrine_Record_Listener_Interface|Doctrine_Overloadable<Doctrine_Record_Listener_Interface> $listener
     * @return $this
     */
    public function setRecordListener(Doctrine_Record_Listener_Interface|Doctrine_Overloadable $listener): self
    {
        // @phpstan-ignore-next-line
        if (!($listener instanceof Doctrine_Record_Listener_Interface)
            && !($listener instanceof Doctrine_Overloadable)
        ) {
            throw new Doctrine_Exception("Couldn't set eventlistener. Record listeners should implement either Doctrine_Record_Listener_Interface or Doctrine_Overloadable");
        }
        $this->attributes[Doctrine_Core::ATTR_RECORD_LISTENER] = $listener;

        return $this;
    }

    /**
     * @phpstan-param Doctrine_EventListener_Interface|Doctrine_Overloadable<Doctrine_EventListener_Interface> $listener
     * @return $this
     */
    public function addListener(Doctrine_EventListener_Interface|Doctrine_Overloadable $listener, ?string $name = null): self
    {
        if (!isset($this->attributes[Doctrine_Core::ATTR_LISTENER])
            || !($this->attributes[Doctrine_Core::ATTR_LISTENER] instanceof Doctrine_EventListener_Chain)
        ) {
            $this->attributes[Doctrine_Core::ATTR_LISTENER] = new Doctrine_EventListener_Chain();
        }
        $this->attributes[Doctrine_Core::ATTR_LISTENER]->add($listener, $name);

        return $this;
    }

    /**
     * @phpstan-return Doctrine_EventListener_Interface|Doctrine_Overloadable<Doctrine_EventListener_Interface>
     */
    public function getListener(): Doctrine_EventListener_Interface|Doctrine_Overloadable
    {
        if (!isset($this->attributes[Doctrine_Core::ATTR_LISTENER])) {
            if (isset($this->parent)) {
                return $this->parent->getListener();
            }
            throw new Doctrine_EventListener_Exception('Could not get a listener');
        }
        return $this->attributes[Doctrine_Core::ATTR_LISTENER];
    }

    /**
     * @phpstan-param Doctrine_EventListener_Interface|Doctrine_Overloadable<Doctrine_EventListener_Interface> $listener
     * @return $this
     */
    public function setListener(Doctrine_EventListener_Interface|Doctrine_Overloadable $listener): self
    {
        // @phpstan-ignore-next-line
        if (!$listener instanceof Doctrine_EventListener_Interface
            && !$listener instanceof Doctrine_Overloadable
        ) {
            throw new Doctrine_EventListener_Exception("Couldn't set eventlistener. EventListeners should implement either Doctrine_EventListener_Interface or Doctrine_Overloadable");
        }
        $this->attributes[Doctrine_Core::ATTR_LISTENER] = $listener;

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
        $this->setAttribute(Doctrine_Core::ATTR_DEFAULT_TABLE_CHARSET, $charset);
    }

    public function getCharset(): mixed
    {
        return $this->getAttribute(Doctrine_Core::ATTR_DEFAULT_TABLE_CHARSET);
    }

    public function setCollate(string $collate): void
    {
        $this->setAttribute(Doctrine_Core::ATTR_DEFAULT_TABLE_COLLATE, $collate);
    }

    public function getCollate(): mixed
    {
        return $this->getAttribute(Doctrine_Core::ATTR_DEFAULT_TABLE_COLLATE);
    }

    /**
     * sets a parent for this configurable component
     * the parent must be configurable component itself
     */
    public function setParent(Doctrine_Configurable $component): void
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
