<?php

/**
 * @phpstan-template T of \Doctrine_Record
 * @phpstan-implements \IteratorAggregate<T>
 */
class Doctrine_Collection extends Doctrine_Access implements Countable, IteratorAggregate, Serializable
{
    /**
     * @var Doctrine_Record[] $data an array containing the records of this collection
     * @phpstan-var T[]
     */
    protected array $data = [];

    /**
     * @var Doctrine_Table $table each collection has only records of specified table
     * @phpstan-var Doctrine_Table<T>
     */
    protected Doctrine_Table $table;

    /**
     * @var Doctrine_Record[] $snapshot a snapshot of the fetched data
     * @phpstan-var T[]
     */
    protected array $snapshot = [];

    /**
     * @var Doctrine_Record|null $reference collection can belong to a record
     */
    protected ?Doctrine_Record $reference;

    /**
     * @var string $referenceField the reference field of the collection
     */
    protected string $referenceField;

    /**
     * @var Doctrine_Relation the record this collection is related to, if any
     */
    protected Doctrine_Relation $relation;

    /**
     * @var string $keyColumn the name of the column that is used for collection key mapping
     */
    protected string $keyColumn;

    /**
     * @var Doctrine_Null $null used for extremely fast null value testing
     */
    protected static Doctrine_Null $null;

    /**
     * @phpstan-param Doctrine_Table<T>|class-string<T> $table
     */
    public function __construct(Doctrine_Table|string $table, ?string $keyColumn = null)
    {
        static::$null = Doctrine_Null::instance();

        if (!($table instanceof Doctrine_Table)) {
            $table = Doctrine_Core::getTable($table);
        }

        $this->table = $table;

        if ($keyColumn === null) {
            $keyColumn = $table->getBoundQueryPart('indexBy');
        }

        if ($keyColumn === null) {
            $keyColumn = $table->getAttribute(Doctrine_Core::ATTR_COLL_KEY);
        }

        if ($keyColumn !== null) {
            $this->keyColumn = $keyColumn;
        }
    }

    /**
     * @phpstan-param  Doctrine_Table<T>|class-string<T> $table
     * @psalm-param    class-string|null $class
     * @phpstan-param  class-string<Doctrine_Collection<T>>|null $class
     * @phpstan-return Doctrine_Collection<T>
     */
    public static function create(Doctrine_Table|string $table, ?string $keyColumn = null, ?string $class = null): Doctrine_Collection
    {
        if ($class === null) {
            if (!$table instanceof Doctrine_Table) {
                $table = Doctrine_Core::getTable($table);
            }
            /** @phpstan-var class-string<Doctrine_Collection> $class */
            $class = $table->getAttribute(Doctrine_Core::ATTR_COLLECTION_CLASS);
        }

        return new $class($table, $keyColumn);
    }

    /**
     * Get the table this collection belongs to
     *
     * @phpstan-return Doctrine_Table<T>
     */
    public function getTable(): Doctrine_Table
    {
        return $this->table;
    }

    /**
     * Set the data for the Doctrin_Collection instance
     *
     * @param Doctrine_Record[] $data
     * @phpstan-param T[] $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * This method is automatically called when this Doctrine_Collection is serialized
     */
    public function serialize(): string
    {
        $vars = get_object_vars($this);

        unset($vars['reference']);
        unset($vars['referenceField']);
        unset($vars['relation']);

        $vars['table'] = $vars['table']->getComponentName();

        return serialize($vars);
    }

    /**
     * This method is automatically called everytime a Doctrine_Collection object is unserialized
     */
    public function unserialize(string $serialized): void
    {
        $manager    = Doctrine_Manager::getInstance();
        $connection = $manager->getCurrentConnection();

        $array = unserialize($serialized);

        foreach ($array as $name => $values) {
            if ($name === 'table') {
                $values = $connection->getTable((string) $values);
            }
            $this->$name = $values;
        }

        $keyColumn = isset($array['keyColumn']) ? $array['keyColumn'] : null;
        if ($keyColumn === null) {
            $keyColumn = $this->table->getBoundQueryPart('indexBy');
        }

        if ($keyColumn !== null) {
            $this->keyColumn = $keyColumn;
        }
    }

    /**
     * Sets the key column for this collection
     * @return $this
     */
    public function setKeyColumn(string $column): self
    {
        $this->keyColumn = $column;
        return $this;
    }

    /**
     * Get the name of the key column
     */
    public function getKeyColumn(): string
    {
        return $this->keyColumn;
    }

    /**
     * Get all the records as an array
     *
     * @return Doctrine_Record[]
     * @phpstan-return T[]
     */
    public function getData(): array
    {
        return array_filter($this->data, fn($r) => !$r instanceof Doctrine_Null);
    }

    /**
     * Get the first record in the collection
     *
     * @phpstan-return ?T
     */
    public function getFirst(): ?Doctrine_Record
    {
        $r = reset($this->data);
        return $r === false ? null : $r;
    }

    /**
     * Get the last record in the collection
     *
     * @phpstan-return ?T
     */
    public function getLast(): ?Doctrine_Record
    {
        $r = end($this->data);
        return $r === false ? null : $r;
    }

    /**
     * Get the last record in the collection
     *
     * @phpstan-return ?T
     */
    public function end(): ?Doctrine_Record
    {
        return $this->getLast();
    }

    /**
     * Get the current key
     *
     * @return int|string|null
     */
    public function key(): int|string|null
    {
        return key($this->data);
    }

    /**
     * Sets a reference pointer
     */
    public function setReference(Doctrine_Record $record, Doctrine_Relation $relation): void
    {
        $this->reference = $record;
        $this->relation  = $relation;

        if ($relation instanceof Doctrine_Relation_ForeignKey
            || $relation instanceof Doctrine_Relation_LocalKey
        ) {
            $this->referenceField = $relation->getForeignFieldName();

            $value = $record->get($relation->getLocalFieldName());

            foreach ($this->data as $record) {
                if ($value !== null) {
                    $record->set($this->referenceField, $value, false);
                } else {
                    $record->set($this->referenceField, $this->reference, false);
                }
            }
        } elseif ($relation instanceof Doctrine_Relation_Association) {
        }
    }

    /**
     * Get reference to Doctrine_Record instance
     */
    public function getReference(): ?Doctrine_Record
    {
        return $this->reference;
    }

    /**
     * Removes a specified collection element
     * @phpstan-return T|null
     */
    public function remove(mixed $key): ?Doctrine_Record
    {
        $removed = $this->data[$key];

        unset($this->data[$key]);
        return $removed;
    }

    /**
     * Whether or not this collection contains a specified element
     *
     * @param  mixed $key the key of the element
     */
    public function contains(mixed $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Search a Doctrine_Record instance
     *
     * @param         Doctrine_Record $record
     * @phpstan-param T $record
     */
    public function search(Doctrine_Record $record): int|string|null
    {
        $result = array_search($record, $this->data, true);
        return $result === false ? null : $result;
    }

    /**
     * Gets a record for given key
     *
     * There are two special cases:
     *
     * 1. if null is given as a key a new record is created and attached
     * at the end of the collection
     *
     * 2. if given key does not exist, then a new record is create and attached
     * to the given key
     *
     * Collection also maps referential information to newly created records
     *
     * @param mixed $key the key of the element
     * @return Doctrine_Record return a specified record
     * @phpstan-return T
     */
    public function get(mixed $key): Doctrine_Record
    {
        if (!isset($this->data[$key])) {
            $record = $this->table->create();

            if (isset($this->referenceField) && $this->reference !== null) {
                $value = $this->reference->get($this->relation->getLocalFieldName());
                $record->set($this->referenceField, $value ?? $this->reference, false);
            }
            if ($key === null) {
                $this->data[] = $record;
            } else {
                $this->data[$key] = $record;
            }

            if (isset($this->keyColumn)) {
                $record->set($this->keyColumn, $key);
            }

            return $record;
        }

        return $this->data[$key];
    }

    /**
     * Get array of primary keys for all the records in the collection
     *
     * @return array<int, mixed>                an array containing all primary keys
     */
    public function getPrimaryKeys(): array
    {
        $list = [];
        $name = $this->table->getIdentifier();

        foreach ($this->data as $record) {
            // @phpstan-ignore-next-line
            if (is_array($record) && isset($record[$name])) {
                $list[] = $record[$name];
            } else {
                $list[] = $record->getIncremented();
            }
        }
        return $list;
    }

    /**
     * Get all keys of the data in the collection
     *
     * @return array<int,int|string>
     */
    public function getKeys(): array
    {
        return array_keys($this->data);
    }

    /**
     * Gets the number of records in this collection
     * This class implements interface countable
     */
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * Set a Doctrine_Record instance to the collection
     *
     * @param         integer         $key
     * @param         Doctrine_Record $record
     * @phpstan-param T $record
     * @return        void
     */
    public function set($key, $record)
    {
        if (isset($this->referenceField)) {
            $record->set($this->referenceField, $this->reference, false);
        }

        $this->data[$key] = $record;
    }

    /**
     * Adds a record to collection
     *
     * @param Doctrine_Record $record record to be added
     * @phpstan-param T $record
     * @param string|null $key optional key for the record
     */
    public function add($record, ?string $key = null): void
    {
        if (isset($this->referenceField) && $this->reference !== null) {
            $value = $this->reference->get($this->relation->getLocalFieldName());
            $record->set($this->referenceField, $value ?? $this->reference, false);
            /** @var Doctrine_Table */
            $table = $this->relation['table'];
            $relations = $table->getRelations();
            foreach ($relations as $relation) {
                if ($this->relation['class'] == $relation['localTable']->name && $relation->getLocal() == $this->relation->getForeignFieldName()) {
                    $record->{$relation['alias']} = $this->reference;
                    break;
                }
            }
        }
        /**
         * for some weird reason in_array cannot be used here (php bug ?)
         *
         * if used it results in fatal error : [ nesting level too deep ]
         */
        foreach ($this->data as $val) {
            if ($val === $record) {
                return;
            }
        }

        if (isset($key)) {
            if (isset($this->data[$key])) {
                return;
            }
            $this->data[$key] = $record;
            return;
        }

        if (isset($this->keyColumn)) {
            $value = $record->get($this->keyColumn);
            if ($value === null) {
                throw new Doctrine_Collection_Exception("Couldn't create collection index. Record field '{$this->keyColumn}' was null.");
            }
            $this->data[$value] = $record;
        } else {
            $this->data[] = $record;
        }
    }

    /**
     * Merges collection into $this and returns merged collection
     *
     * @param         Doctrine_Collection $coll
     * @phpstan-param Doctrine_Collection<T> $coll
     * @return        $this
     */
    public function merge(Doctrine_Collection $coll): self
    {
        $localBase = $this->getTable()->getComponentName();
        $otherBase = $coll->getTable()->getComponentName();

        // @phpstan-ignore-next-line
        if ($otherBase != $localBase && !is_subclass_of($otherBase, $localBase)) {
            throw new Doctrine_Collection_Exception("Can't merge collections with incompatible record types");
        }

        foreach ($coll->getData() as $record) {
            $this->add($record);
        }

        return $this;
    }

    /**
     * Load all relationships or the named relationship passed
     */
    public function loadRelated(?string $name = null): ?Doctrine_Query
    {
        $list  = [];
        $query = $this->table->createQuery();

        if (!isset($name)) {
            foreach ($this->data as $record) {
                $value = $record->getIncremented();
                if ($value !== null) {
                    $list[] = $value;
                }
            }
            $query->where($this->table->getComponentName() . '.id IN (' . substr(str_repeat('?, ', count($list)), 0, -2) . ')');
            if (!$list) {
                $query->where($this->table->getComponentName() . '.id IN (' . substr(str_repeat('?, ', count($list)), 0, -2) . ')', $list);
            }

            return $query;
        }

        $rel = $this->table->getRelation($name);

        if ($rel instanceof Doctrine_Relation_LocalKey || $rel instanceof Doctrine_Relation_ForeignKey) {
            foreach ($this->data as $record) {
                $list[] = $record[$rel->getLocal()];
            }
        } else {
            foreach ($this->data as $record) {
                $value = $record->getIncremented();
                if ($value !== null) {
                    $list[] = $value;
                }
            }
        }

        if (!$list) {
            return null;
        }

        if ($rel instanceof Doctrine_Relation_Association) {
            $dql = $rel->getRelationDql(count($list), 'collection');
        } else {
            $dql = $rel->getRelationDql(count($list));
        }

        $coll = $query->query($dql, $list);

        $this->populateRelated($name, $coll);

        return null;
    }

    /**
     * Populate the relationship $name for all records in the passed collection
     *
     * @phpstan-param Doctrine_Collection<T> $coll
     */
    public function populateRelated(string $name, Doctrine_Collection $coll): void
    {
        $rel     = $this->table->getRelation($name);
        $table   = $rel->getTable();
        $foreign = $rel->getForeign();
        $local   = $rel->getLocal();

        if ($rel instanceof Doctrine_Relation_LocalKey) {
            foreach ($this->data as $key => $record) {
                foreach ($coll as $k => $related) {
                    if ($related[$foreign] == $record[$local]) {
                        $this->data[$key]->setRelated($name, $related);
                    }
                }
            }
        } elseif ($rel instanceof Doctrine_Relation_ForeignKey) {
            foreach ($this->data as $key => $record) {
                if (!$record->exists()) {
                    continue;
                }
                $sub = Doctrine_Collection::create($table);

                foreach ($coll as $k => $related) {
                    if ($related[$foreign] == $record[$local]) {
                        $sub->add($related);
                        $coll->remove($k);
                    }
                }

                $this->data[$key]->setRelated($name, $sub);
            }
        } elseif ($rel instanceof Doctrine_Relation_Association) {
            $identifier = $this->table->getIdentifier();
            $asf        = $rel->getAssociationFactory();
            $name       = $table->getComponentName();

            foreach ($this->data as $key => $record) {
                if (!$record->exists()) {
                    continue;
                }
                $sub = Doctrine_Collection::create($table);
                foreach ($coll as $k => $related) {
                    if ($related->get($local) == $record[$identifier]) {
                        $sub->add($related->get($name));
                    }
                }
                $this->data[$key]->setRelated($name, $sub);
            }
        }
    }

    /**
     * Get normal iterator - an iterator that will not expand this collection
     */
    public function getNormalIterator(): Doctrine_Collection_Iterator_Normal
    {
        return new Doctrine_Collection_Iterator_Normal($this);
    }

    /**
     * Takes a snapshot from this collection
     *
     * snapshots are used for diff processing, for example
     * when a fetched collection has three elements, then two of those
     * are being removed the diff would contain one element
     *
     * Doctrine_Collection::save() attaches the diff with the help of last
     * snapshot.
     *
     * @return $this
     */
    public function takeSnapshot(): self
    {
        $this->snapshot = $this->data;

        return $this;
    }

    /**
     * Gets the data of the last snapshot
     *
     * @return         \Doctrine_Record[]    returns the data in last snapshot
     * @phpstan-return T[]
     */
    public function getSnapshot(): array
    {
        return $this->snapshot;
    }

    /**
     * Processes the difference of the last snapshot and the current data
     *
     * an example:
     * Snapshot with the objects 1, 2 and 4
     * Current data with objects 2, 3 and 5
     *
     * The process would remove object 4
     *
     * @return $this
     */
    public function processDiff(): self
    {
        foreach (array_udiff($this->snapshot, $this->data, [$this, 'compareRecords']) as $record) {
            $record->delete();
        }

        return $this;
    }

    /**
     * Mimics the result of a $query->execute(array(), Doctrine_Core::HYDRATE_ARRAY);
     * @phpstan-return array<string, array<string, mixed>|null>
     */
    public function toArray(bool $deep = true, bool $prefixKey = false): array
    {
        $data = [];
        /** @var Doctrine_Record $record */
        foreach ($this as $key => $record) {
            $key = $prefixKey ? get_class($record) . '_' . $key : $key;
            $data[(string) $key] = $record->toArray($deep, $prefixKey);
        }
        return $data;
    }

    /**
     * Build an array made up of the values from the 2 specified columns
     * @phpstan-return array<string, mixed>
     */
    public function toKeyValueArray(string $key, string $value): array
    {
        $result = [];
        /** @var Doctrine_Record $record */
        foreach ($this as $record) {
            $result[(string) $record->$key] = $record->$value;
        }
        return $result;
    }

    /**
     * Populate a Doctrine_Collection from an array of data
     * @param mixed[] $array
     * @phpstan-param array<string, mixed>[] $array
     */
    public function fromArray(array $array, bool $deep = true): void
    {
        $data = [];
        foreach ($array as $rowKey => $row) {
            $this[$rowKey]->fromArray($row, $deep);
        }
    }

    /**
     * synchronizes a Doctrine_Collection with data from an array
     *
     * it expects an array representation of a Doctrine_Collection similar to the return
     * value of the toArray() method. It will create Dectrine_Records that don't exist
     * on the collection, update the ones that do and remove the ones missing in the $array
     *
     * @param mixed[][] $array representation of a Doctrine_Collection
     */
    public function synchronizeWithArray(array $array): void
    {
        /** @var Doctrine_Record $record */
        foreach ($this as $key => $record) {
            if (isset($array[$key])) {
                $record->synchronizeWithArray($array[$key]);
                unset($array[$key]);
            } else {
                // remove records that don't exist in the array
                $this->remove($key);
            }
        }
        // create new records for each new row in the array
        foreach ($array as $rowKey => $row) {
            $this[$rowKey]->fromArray($row);
        }
    }

    /**
     * @param  array<mixed>[] $array representation of a Doctrine_Collection
     */
    public function synchronizeFromArray(array $array): void
    {
        $this->synchronizeWithArray($array);
    }

    /**
     * Perform a delete diff between the last snapshot and the current data
     *
     * @return         \Doctrine_Record[] $diff
     * @phpstan-return T[]
     */
    public function getDeleteDiff(): array
    {
        return array_udiff($this->snapshot, $this->data, [$this, 'compareRecords']);
    }

    /**
     * Perform a insert diff between the last snapshot and the current data
     *
     * @return         \Doctrine_Record[] $diff
     * @phpstan-return T[]
     */
    public function getInsertDiff(): array
    {
        return array_udiff($this->data, $this->snapshot, [$this, 'compareRecords']);
    }

    /**
     * Compares two records. To be used on _snapshot diffs using array_udiff
     * @phpstan-param T $a
     * @phpstan-param T $b
     */
    protected function compareRecords(Doctrine_Record $a, Doctrine_Record $b): int
    {
        if ($a->getOid() == $b->getOid()) {
            return 0;
        }

        return ($a->getOid() > $b->getOid()) ? 1 : -1;
    }

    /**
     * Saves all records of this collection and processes the
     * difference of the last snapshot and the current data
     *
     * @param  Doctrine_Connection|null $conn        optional connection parameter
     * @param  bool$processDiff
     * @return $this
     */
    public function save(?Doctrine_Connection $conn = null, bool $processDiff = true): self
    {
        if ($conn == null) {
            $conn = $this->table->getConnection();
        }

        try {
            $savepoint = $conn->beginInternalTransaction();
            $savepoint->addCollection($this);

            if ($processDiff) {
                $this->processDiff();
            }

            foreach ($this->getData() as $record) {
                $record->save($conn);
            }

            $savepoint->commit();
        } catch (Throwable $e) {
            if (isset($savepoint)) {
                $savepoint->rollback();
            }
            throw $e;
        }

        return $this;
    }

    /**
     * Replaces all records of this collection and processes the
     * difference of the last snapshot and the current data
     *
     * @param  Doctrine_Connection|null $conn optional connection parameter
     * @return $this
     */
    public function replace(?Doctrine_Connection $conn = null, bool $processDiff = true): self
    {
        if ($conn == null) {
            $conn = $this->table->getConnection();
        }

        try {
            $savepoint = $conn->beginInternalTransaction();
            $savepoint->addCollection($this);

            if ($processDiff) {
                $this->processDiff();
            }

            foreach ($this->getData() as $record) {
                $record->replace($conn);
            }

            $savepoint->commit();
        } catch (Throwable $e) {
            if (isset($savepoint)) {
                $savepoint->rollback();
            }
            throw $e;
        }

        return $this;
    }

    /**
     * Deletes all records from this collection
     *
     * @param Doctrine_Connection|null $conn optional connection parameter
     */
    public function delete(?Doctrine_Connection $conn = null, bool $clearColl = true): self
    {
        if ($conn == null) {
            $conn = $this->table->getConnection();
        }

        try {
            $savepoint = $conn->beginInternalTransaction();
            $savepoint->addCollection($this);

            foreach ($this as $key => $record) {
                $record->delete($conn);
            }

            $savepoint->commit();
        } catch (Throwable $e) {
            if (isset($savepoint)) {
                $savepoint->rollback();
            }
            throw $e;
        }

        if ($clearColl) {
            $this->clear();
        }

        return $this;
    }

    /**
     * Clears the collection.
     */
    public function clear(): void
    {
        $this->data = [];
    }

    /**
     * Frees the resources used by the collection.
     * WARNING: After invoking free() the collection is no longer considered to
     * be in a useable state. Subsequent usage may result in unexpected behavior.
     */
    public function free(bool $deep = false): void
    {
        foreach ($this->getData() as $key => $record) {
            $record->free($deep);
        }

        $this->data = [];

        if (isset($this->reference)) {
            $this->reference->free($deep);
            unset($this->reference);
        }
    }

    /**
     * Get collection data iterator
     *
     * @return \ArrayIterator<mixed,Doctrine_Record>
     * @phpstan-return \ArrayIterator<mixed,T>
     */
    public function getIterator(): ArrayIterator
    {
        $data = $this->data;
        return new ArrayIterator($data);
    }

    /**
     * Returns the relation object
     */
    public function getRelation(): Doctrine_Relation
    {
        return $this->relation;
    }

    /**
     * checks if one of the containing records is modified
     * returns true if modified, false otherwise
     */
    final public function isModified(): bool
    {
        $dirty = (count($this->getInsertDiff()) > 0 || count($this->getDeleteDiff()) > 0);
        if (!$dirty) {
            foreach ($this as $record) {
                if ($dirty = $record->isModified()) {
                    break;
                }
            }
        }
        return $dirty;
    }
}
