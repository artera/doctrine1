<?php

namespace Doctrine1;

use ArrayIterator;
use Doctrine1\Column\Type;
use Doctrine1\Record\State;
use Doctrine1\Serializer;
use Doctrine1\Deserializer;

/**
 * @phpstan-template T of Table
 * @phpstan-implements \ArrayAccess<string, mixed>
 */
abstract class Record implements \Countable, \IteratorAggregate, \Serializable, \ArrayAccess
{
    /**
     * the primary keys of this object
     */
    protected array $_id = [];

    /**
     * each element is one of 3 following types:
     * - simple type (int, string) - field has a scalar value
     * - null - field has NULL value in DB
     * - None - field value is unknown, it wasn't loaded yet
     *
     * the record data
     * @phpstan-var array<string, mixed>
     */
    protected array $_data = [];

    /**
     * the values array, aggregate values and such are mapped into this array
     */
    protected array $_values = [];

    /**
     * the state of this record
     */
    protected State $_state;

    /**
     * an array containing field names that were modified in the previous transaction
     * @var string[]
     */
    protected array $_lastModified = [];

    /**
     * an array containing field names that have been modified
     * @var string[]
     */
    protected array $_modified = [];

    /**
     * an array of the old values from set properties
     */
    protected array $_oldValues = [];

    /**
     * error stack object
     */
    protected ?Validator\ErrorStack $_errorStack = null;

    /**
     * an array containing all the references
     * @phpstan-var array<string, Record|Collection|None|null>
     */
    protected array $_references = [];

    /**
     * Collection of objects needing to be deleted on save
     */
    protected array $_pendingDeletes = [];

    /**
     * Array of pending un links in format alias => keys to be executed after save
     */
    protected array $_pendingUnlinks = [];

    /**
     * Array of custom accessors for cache
     */
    protected static array $_customAccessors = [];

    /**
     * Array of custom mutators for cache
     */
    protected static array $_customMutators = [];

    /**
     * Whether or not to serialize references when a Record is serialized
     */
    protected bool $_serializeReferences = false;

    /**
     * Array containing the save hooks and events that have been invoked
     */
    protected array $_invokedSaveHooks = [];

    /**
     * this index is used for creating object identifiers
     */
    private static int $_index = 1;

    /**
     * object identifier, each Record object has a unique object identifier
     */
    private int $_oid;

    /**
     * reference to associated Table instance
     * @phpstan-var T
     */
    protected Table $_table;

    /**
     * @param Table|null $table a Table object or null,
     *                                   if null the table object is
     *                                   retrieved from current
     *                                   connection
     * @phpstan-param T|null $table
     *
     * @param boolean $isNewEntry whether or not this record is transient
     *
     * @throws Connection\Exception   if object is created using the new operator and there are no
     *                                         open connections
     * @throws Record\Exception       if the cleanData operation fails somehow
     */
    public function __construct(?Table $table = null, bool $isNewEntry = false)
    {
        if ($table !== null) {
            $this->_table = $table;
            $exists       = !$isNewEntry;
        } else {
            // @phpstan-ignore-next-line
            $this->_table = Core::getTable(static::class);
            $exists       = false;
        }

        // Check if the current connection has the records table in its registry
        // If not this record is only used for creating table definition and setting up
        // relations.
        if (!$this->_table->getConnection()->hasTable($this->_table->getComponentName())) {
            return;
        }

        $this->_oid = self::$_index;

        self::$_index++;

        // get the data array
        $this->_data = $this->_table->getData();

        // get the column count
        $count = count($this->_data);

        $this->_values = $this->cleanData($this->_data);

        $this->prepareIdentifiers($exists);

        if (!$exists) {
            if ($count > count($this->_values)) {
                $this->_state = State::TDIRTY;
            } else {
                $this->_state = State::TCLEAN;
                $this->resetModified();
            }

            // set the default values for this record
            $this->assignDefaultValues();
        } else {
            $this->_state = State::CLEAN;
            $this->resetModified();

            if ($this->isInProxyState()) {
                $this->_state = State::PROXY;
            }
        }

        // Table does not have the repository yet during dummy record creation.
        /** @var Table\Repository|null */
        $repository = $this->_table->getRepository();

        // Fix for #1682 and #1841.
        if ($repository) {
            $repository->add($this);
            $this->construct();
        }
    }

    /**
     * Set whether or not to serialize references.
     * This is used by caching since we want to serialize references when caching
     * but not when just normally serializing a instance
     */
    public function serializeReferences(?bool $bool = null): bool
    {
        if ($bool !== null) {
            $this->_serializeReferences = $bool;
        }
        return $this->_serializeReferences;
    }

    /**
     * this method is used for setting up relations and attributes
     * it should be implemented by child classes
     */
    public function setUp(): void
    {
    }

    public function setTableDefinition(): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the constructor procedure
     */
    public function construct(): void
    {
    }

    /**
     * @return integer  the object identifier
     */
    public function getOid(): int
    {
        return $this->_oid;
    }

    /**
     * calls a subclass hook. Idempotent until @see clearInvokedSaveHooks() is called.
     *
     * <code>
     * $this->invokeSaveHooks('pre', 'save');
     * </code>
     *
     * @phpstan-param 'post'|'pre' $when
     * @phpstan-param 'serialize'|'unserialize'|'save'|'delete'|'update'|'insert'|'validate'|'dqlSelect'|'dqlDelete'|'hydrate' $type
     * @param  Event $event event raised
     * @return Event        the event generated using the type, if not specified
     */
    public function invokeSaveHooks(string $when, string $type, ?Event $event = null): Event
    {
        $func = $when . ucfirst($type);

        if ($event === null) {
            $constant = constant(Event::class . '::RECORD_' . strtoupper($type));
            $event = new Event($this, $constant);
        }

        if (!isset($this->_invokedSaveHooks[$func])) {
            $this->$func($event);
            $this->getTable()->getRecordListener()->$func($event);

            $this->_invokedSaveHooks[$func] = $event;
        } else {
            $event = $this->_invokedSaveHooks[$func];
        }

        return $event;
    }

    /**
     * makes all the already used save hooks available again
     */
    public function clearInvokedSaveHooks(): void
    {
        $this->_invokedSaveHooks = [];
    }

    /**
     * tests validity of the record using the current data.
     *
     * @param  boolean $deep  run the validation process on the relations
     * @param  boolean $hooks invoke save hooks before start
     * @return boolean        whether or not this record is valid
     */
    public function isValid(bool $deep = false, bool $hooks = true): bool
    {
        if (!$this->_table->getValidate()) {
            return true;
        }

        if ($this->_state->isLocked()) {
            return true;
        }

        if ($hooks) {
            $this->invokeSaveHooks('pre', 'save');
            $this->invokeSaveHooks('pre', $this->exists() ? 'update' : 'insert');
        }

        // Clear the stack from any previous errors.
        $this->getErrorStack()->clear();

        $stateBeforeLock = $this->_state;
        $this->_state = $this->_state->lock();

        try {
            // Run validation process
            $event = new Event($this, Event::RECORD_VALIDATE);
            $this->preValidate($event);
            $this->getTable()->getRecordListener()->preValidate($event);

            $validator = new Validator();
            $validator->validateRecord($this);
            $this->validate();

            if ($this->_state->isTransient()) {
                $this->validateOnInsert();
            } else {
                $this->validateOnUpdate();
            }

            $this->getTable()->getRecordListener()->postValidate($event);
            $this->postValidate($event);

            $valid = $this->getErrorStack()->count() == 0 ? true : false;
            if ($valid && $deep) {
                foreach ($this->_references as $reference) {
                    if ($reference instanceof Record) {
                        if (!$valid = $reference->isValid($deep)) {
                            break;
                        }
                    } elseif ($reference instanceof Collection) {
                        foreach ($reference as $record) {
                            if (!$valid = $record->isValid($deep)) {
                                break;
                            }
                        }
                    }
                }
            }
        } finally {
            $this->_state = $stateBeforeLock;
        }

        return $valid;
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure, doing any custom / specialized
     * validations that are neccessary.
     */
    protected function validate(): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure only when the record is going to be
     * updated.
     */
    protected function validateOnUpdate(): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure only when the record is going to be
     * inserted into the data store the first time.
     */
    protected function validateOnInsert(): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the serializing procedure.
     */
    public function preSerialize(Event $event): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the serializing procedure.
     */
    public function postSerialize(Event $event): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the serializing procedure.
     */
    public function preUnserialize(Event $event): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the serializing procedure.
     */
    public function postUnserialize(Event $event): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure.
     */
    public function preSave(Event $event): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure.
     */
    public function postSave(Event $event): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the deletion procedure.
     */
    public function preDelete(Event $event): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the deletion procedure.
     */
    public function postDelete(Event $event): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure only when the record is going to be
     * updated.
     */
    public function preUpdate(Event $event): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure only when the record is going to be
     * updated.
     */
    public function postUpdate(Event $event): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure only when the record is going to be
     * inserted into the data store the first tim
     */
    public function preInsert(Event $event): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure only when the record is going to be
     * inserted into the data store the first time
     */
    public function postInsert(Event $event): void
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure. Useful for cleaning up data before
     * validating it.
     */
    public function preValidate(Event $event): void
    {
    }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure.
     */
    public function postValidate(Event $event): void
    {
    }

    /**
     * Empty template method to provide Record classes with the ability to alter DQL select
     * queries at runtime
     */
    public function preDqlSelect(Event $event): void
    {
    }

    /**
     * Empty template method to provide Record classes with the ability to alter DQL update
     * queries at runtime
     */
    public function preDqlUpdate(Event $event): void
    {
    }

    /**
     * Empty template method to provide Record classes with the ability to alter DQL delete
     * queries at runtime
     */
    public function preDqlDelete(Event $event): void
    {
    }

    /**
     * Empty template method to provide Record classes with the ability to alter hydration
     * before it runs
     */
    public function preHydrate(Event $event): void
    {
    }

    /**
     * Empty template method to provide Record classes with the ability to alter hydration
     * after it runs
     */
    public function postHydrate(Event $event): void
    {
    }

    /**
     * Get the record error stack as a human readable string.
     * Useful for outputting errors to user via web browser
     */
    public function getErrorStackAsString(): ?string
    {
        $errorStack = $this->getErrorStack();

        if (!count($errorStack)) {
            return null;
        }

        $message = sprintf("Validation failed in class %s\n\n", static::class);

        $message .= '  ' . count($errorStack) . ' field' . (count($errorStack) > 1 ? 's' : null) . ' had validation error' . (count($errorStack) > 1 ? 's' : null) . ":\n\n";
        foreach ($errorStack as $field => $errors) {
            $message .= '    * ' . count($errors) . ' validator' . (count($errors) > 1 ? 's' : null) . " failed on $field (" . implode(', ', $errors) . ")\n";
        }
        return $message;
    }

    /**
     * retrieves the ErrorStack. To be called after a failed validation attempt (@see isValid()).
     *
     * @return Validator\ErrorStack    returns the errorStack associated with this record
     */
    public function getErrorStack(): Validator\ErrorStack
    {
        if ($this->_errorStack === null) {
            $this->_errorStack = new Validator\ErrorStack(static::class);
        }
        return $this->_errorStack;
    }

    /**
     * assigns the ErrorStack or returns it if called without parameters
     *
     * @param  Validator\ErrorStack|null $stack errorStack to be assigned for this record
     * @return Validator\ErrorStack returns the errorStack associated with this record
     */
    public function errorStack(?Validator\ErrorStack $stack = null): Validator\ErrorStack
    {
        if ($stack !== null) {
            $this->_errorStack = $stack;
            return $stack;
        } else {
            return $this->getErrorStack();
        }
    }

    /**
     * Assign the inheritance column values
     */
    public function assignInheritanceValues(): void
    {
        $map = $this->_table->inheritanceMap;
        foreach ($map as $k => $v) {
            $k   = $this->_table->getFieldName($k);
            $old = $this->get($k, false, accessors: true);

            if (((string) $old !== (string) $v || $old === null) && !in_array($k, $this->_modified)) {
                $this->set($k, $v, mutators: true);
            }
        }
    }

    /**
     * sets the default values for records internal data
     *
     * @param  boolean $overwrite whether or not to overwrite the already set values
     */
    public function assignDefaultValues(bool $overwrite = false): bool
    {
        if (!$this->_table->hasDefaultValues()) {
            return false;
        }

        foreach ($this->_data as $fieldName => $value) {
            $default = $this->_table->getDefaultValueOf($fieldName);

            if ($default === null) {
                continue;
            }

            if ($value instanceof None || $overwrite) {
                $this->_data[$fieldName] = $default;
                $this->_modified[] = $fieldName;
                $this->_state = State::TDIRTY;
            }
        }

        return true;
    }

    /**
     * leaves the $data array only with values whose key is a field inside this
     * record and returns the values that were removed from $data. Also converts
     * any values of 'null' to objects of type None.
     *
     * @param  array $data data array to be cleaned
     * @return array values cleaned from data
     */
    public function cleanData(array &$data, bool $deserialize = true): array
    {
        $tmp  = $data;
        $data = [];

        $deserializers = $deserialize ? $this->getDeserializers() : [];

        $fieldNames = $this->_table->getFieldNames();

        // Fix field name case first
        foreach (array_keys($tmp) as $fieldName) {
            $fixedFieldName = Lib::arrayCISearch($fieldName, $fieldNames);
            if ($fixedFieldName !== null && $fixedFieldName !== $fieldName) {
                $tmp[$fixedFieldName] = $tmp[$fieldName];
                unset($tmp[$fieldName]);
            }
            unset($fixedFieldName);
        }

        foreach ($fieldNames as $fieldName) {
            if (isset($tmp[$fieldName])) { // value present
                if (!empty($deserializers)) {
                    $tmp[$fieldName] = $this->_table->deserializeColumnValue($tmp[$fieldName], $fieldName, $deserializers);
                }
                $data[$fieldName] = $tmp[$fieldName];
            } elseif (array_key_exists($fieldName, $tmp)) { // null
                $data[$fieldName] = null;
            } elseif (!isset($this->_data[$fieldName])) { // column not in data
                $data[$fieldName] = None::instance();
            }
            unset($tmp[$fieldName]);
        }

        return $tmp;
    }

    /**
     * hydrates this object from given array
     *
     * @param array $data
     * @param boolean $overwriteLocalChanges whether to overwrite the unsaved (dirty) data
     */
    public function hydrate(array $data, bool $overwriteLocalChanges = true): void
    {
        $cleanData = $this->cleanData($data);

        if ($overwriteLocalChanges) {
            $this->_values = array_merge($this->_values, $cleanData);
            $this->_data = array_merge($this->_data, $data);
            $this->_modified = [];
            $this->_oldValues = [];
        } else {
            $this->_values = array_merge($cleanData, $this->_values);
            $this->_data = array_merge($data, $this->_data);
        }

        if (!$this->isModified() && $this->isInProxyState()) {
            $this->_state = State::PROXY;
        }
    }

    /**
     * prepares identifiers for later use
     *
     * @param  boolean $exists whether or not this record exists in persistent data store
     */
    private function prepareIdentifiers(bool $exists = true): void
    {
        if ($this->_table->getIdentifierType() === null) {
            return;
        }

        if ($this->_table->getIdentifierType() === IdentifierType::Composite) {
            $names = (array) $this->_table->getIdentifier();

            foreach ($names as $name) {
                if ($this->_data[$name] instanceof None) {
                    $this->_id[$name] = null;
                } else {
                    $this->_id[$name] = $this->_data[$name];
                }
            }

            return;
        }

        $name = $this->_table->getIdentifier();
        if (is_array($name)) {
            $name = $name[0];
        }
        if ($exists) {
            if (isset($this->_data[$name]) && !$this->_data[$name] instanceof None) {
                $this->_id[$name] = $this->_data[$name];
            }
        }
    }

    public function __serialize(): array
    {
        $event = new Event($this, Event::RECORD_SERIALIZE);

        $this->preSerialize($event);
        $this->getTable()->getRecordListener()->preSerialize($event);

        $vars = get_object_vars($this);

        if (!$this->serializeReferences()) {
            unset($vars['_references']);
        }
        unset($vars['_table']);
        unset($vars['_errorStack']);
        unset($vars['_filter']);
        unset($vars['_node']);
        $vars['_state'] = $vars['_state']->value;

        $data = $this->_data;
        if ($this->exists()) {
            $data = array_merge($data, $this->_id);
        }

        foreach ($data as $k => $v) {
            if ($v instanceof None) {
                unset($vars['_data'][$k]);
                continue;
            }

            $type = $this->_table->getTypeOf($k);

            if ($v instanceof Record && $type !== Type::Object) {
                unset($vars['_data'][$k]);
                continue;
            }

            if ($type === Type::Enum) {
                $vars['_data'][$k] = $this->_table->enumIndex($k, $vars['_data'][$k]);
            }
        }

        $this->postSerialize($event);
        $this->getTable()->getRecordListener()->postSerialize($event);

        return $vars;
    }

    public function __unserialize(array $serialized): void
    {
        $event = new Event($this, Event::RECORD_UNSERIALIZE);

        $manager = Manager::getInstance();
        $connection = $manager->getConnectionForComponent(static::class);

        // @phpstan-ignore-next-line
        $this->_table = $connection->getTable(static::class);

        $this->preUnserialize($event);
        $this->getTable()->getRecordListener()->preUnserialize($event);

        foreach ($serialized as $k => $v) {
            if ($k === '_state') {
                $this->_state = State::from($v);
            } else {
                $this->$k = $v;
            }
        }

        foreach ($this->_data as $k => $v) {
            if ($this->_table->getTypeOf($k) === Type::Enum) {
                $this->_data[$k] = $this->_table->enumValue($k, $this->_data[$k]);
            }
        }

        // Remove existing record from the repository and table entity map.
        $this->_table->setData($this->_data);
        $repo = $this->_table->getRepository();
        $existing_record = $this->_table->getRecord();
        if ($existing_record->exists()) {
            if ($repo !== null) {
                $repo->evict($existing_record->getOid());
            }
            $this->_table->removeRecord($existing_record);
        }

        // Add the unserialized record to repository and entity map.
        $this->_oid = self::$_index;
        self::$_index++;
        if ($repo !== null) {
            $repo->add($this);
        }
        $this->_table->addRecord($this);

        $this->cleanData($this->_data);

        $this->prepareIdentifiers($this->exists());

        $this->postUnserialize($event);
        $this->getTable()->getRecordListener()->postUnserialize($event);
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize(string $serialized): void
    {
        $this->__unserialize(unserialize($serialized));
    }

    /**
     * assigns the state of this record or returns it if called without parameters
     *
     * @param State|null $state if set, this method tries to set the record state to $state
     */
    public function state(?State $state = null): State
    {
        if ($state !== null) {
            $this->_state = $state;
            if ($state->isClean()) {
                $this->resetModified();
            }
        }
        return $this->_state;
    }

    /**
     * refresh internal data from the database
     *
     * @param bool $deep If true, fetch also current relations. Caution: this deletes
     *                   any aggregated values you may have queried beforee
     *
     * @throws Record\Exception        When the refresh operation fails (when the database row
     *                                          this record represents does not exist anymore)
     *
     * @return $this|null
     */
    public function refresh(bool $deep = false): ?self
    {
        $id = $this->identifier();
        if (empty($id)) {
            return null;
        }
        $id = array_values($id);

        $overwrite = $this->getTable()->getHydrateOverwrite();
        $this->getTable()->setHydrateOverwrite(true);

        if ($deep) {
            $query = $this->getTable()->createQuery();
            foreach (array_keys($this->_references) as $name) {
                $query->leftJoin(static::class . ".$name");
            }
            $query->where(implode(' = ? AND ', (array)$this->getTable()->getIdentifier()) . ' = ?');
            $this->clearRelated();
            $record = $query->fetchOne($id);
        } else {
            // Use HydrationMode::Array to avoid clearing object relations
            /** @phpstan-var array<string, mixed>|null */
            $record = $this->getTable()->find($id, hydrateArray: true);
            if ($record) {
                $this->hydrate($record);
            }
        }

        $this->getTable()->setHydrateOverwrite($overwrite);

        if ($record === null) {
            throw new Record\Exception('Failed to refresh. Record does not exist.');
        }

        $this->resetModified();

        $this->prepareIdentifiers();

        $this->_state = State::CLEAN;

        return $this;
    }

    /**
     * refresh data of related objects from the database
     *
     * @param string|null $name name of a related component.
     *                     if set, this method only
     *                     refreshes the specified
     *                     related component
     *
     * @return void
     */
    public function refreshRelated(?string $name = null): void
    {
        if ($name === null) {
            foreach ($this->_table->getRelations() as $rel) {
                $alias = $rel->getAlias();
                unset($this->_references[$alias]);
                $reference = $rel->fetchRelatedFor($this);
                if ($reference instanceof Collection) {
                    $this->_references[$alias] = $reference;
                } elseif ($reference instanceof Record) {
                    if ($reference->exists()) {
                        $this->_references[$alias] = $reference;
                    } else {
                        $reference->free();
                    }
                }
            }
        } else {
            unset($this->_references[$name]);
            $rel       = $this->_table->getRelation($name);
            $reference = $rel->fetchRelatedFor($this);
            if ($reference instanceof Collection) {
                $this->_references[$name] = $reference;
            } elseif ($reference instanceof Record) {
                if ($reference->exists()) {
                    $this->_references[$name] = $reference;
                } else {
                    $reference->free();
                }
            }
        }
    }

    /**
     * Clear a related reference or all references
     *
     * @param  string|null $name The relationship reference to clear
     */
    public function clearRelated(?string $name = null): void
    {
        if ($name === null) {
            $this->_references = [];
        } else {
            unset($this->_references[$name]);
        }
    }

    /**
     * Check if a related relationship exists. Will lazily load the relationship
     * in order to check. If the reference didn't already exist and it doesn't
     * exist in the database, the related reference will be cleared immediately.
     *
     * @param  string $name
     * @return boolean Whether or not the related relationship exists
     */
    public function relatedExists(string $name): bool
    {
        if ($this->hasReference($name) && !$this->_references[$name] instanceof None) {
            return true;
        }

        $reference = $this->$name;
        if ($reference instanceof Record) {
            $exists = $reference->exists();
        } elseif ($reference instanceof Collection) {
            throw new Record\Exception(
                'You can only call relatedExists() on a relationship that ' .
                'returns an instance of Record'
            );
        } else {
            $exists = false;
        }

        if (!$exists) {
            $this->clearRelated($name);
        }

        return $exists;
    }

    /**
     * returns the table object for this record.
     *
     * @return Table        a Table object
     * @phpstan-return T
     */
    public function getTable(): Table
    {
        return $this->_table;
    }

    /**
     * return all the internal data (columns)
     *
     * @return array                        an array containing all the properties
     * @phpstan-return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->_data;
    }

    /**
     * returns the value of a property (column). If the property is not yet loaded
     * this method does NOT load it.
     *
     * @param  string $fieldName name of the property
     * @throws Record\Exception    if trying to get an unknown property
     */
    public function rawGet(string $fieldName): mixed
    {
        if (!array_key_exists($fieldName, $this->_data)) {
            throw new Record\Exception('Unknown property ' . $fieldName);
        }
        if ($this->_data[$fieldName] instanceof None) {
            return null;
        }

        return $this->_data[$fieldName];
    }

    /**
     * loads all the uninitialized properties from the database.
     * Used to move a record from PROXY to CLEAN/DIRTY state.
     *
     * @param  array $data overwriting data to load in the record. Instance is hydrated from the table if not specified.
     * @return boolean
     */
    public function load(array $data = []): bool
    {
        // only load the data from database if the Record is in proxy state
        if ($this->exists() && $this->isInProxyState()) {
            $id = $this->identifier();

            if (empty($id)) {
                return false;
            }

            $table = $this->getTable();
            $data  = empty($data) ? $table->find($id, hydrateArray: true) : $data;

            if (is_array($data)) {
                $this->cleanData($data);

                foreach ($data as $field => $value) {
                    if (is_string($field) && (!array_key_exists($field, $this->_data) || $this->_data[$field] instanceof None)) {
                        $this->_data[$field] = $value;
                    }
                }
            }

            if ($this->isModified()) {
                $this->_state = State::DIRTY;
            } elseif (!$this->isInProxyState()) {
                $this->_state = State::CLEAN;
            }

            return true;
        }

        return false;
    }

    /**
     * indicates whether record has any not loaded fields
     */
    public function isInProxyState(): bool
    {
        $count = 0;
        foreach ($this->_data as $value) {
            if (!$value instanceof None) {
                $count++;
            }
        }
        if ($count < $this->_table->getColumnCount()) {
            return true;
        }
        return false;
    }

    /**
     * sets a fieldname to have a custom accessor or check if a field has a custom
     * accessor defined (when called without $accessor parameter).
     */
    public function hasAccessor(string $fieldName, ?string $accessor = null): ?bool
    {
        $componentName = $this->_table->getComponentName();
        if ($accessor) {
            self::$_customAccessors[$componentName][$fieldName] = $accessor;
        } else {
            return (isset(self::$_customAccessors[$componentName][$fieldName]) && self::$_customAccessors[$componentName][$fieldName]);
        }

        return null;
    }

    /**
     * clears the accessor for a field name
     */
    public function clearAccessor(string $fieldName): void
    {
        $componentName = $this->_table->getComponentName();
        unset(self::$_customAccessors[$componentName][$fieldName]);
    }

    /**
     * gets the custom accessor for a field name
     *
     * @param  string $fieldName
     * @return string|null $accessor
     */
    public function getAccessor(string $fieldName): ?string
    {
        if ($this->hasAccessor($fieldName)) {
            $componentName = $this->_table->getComponentName();
            return self::$_customAccessors[$componentName][$fieldName];
        }

        return null;
    }

    /**
     * gets all accessors for this component instance
     */
    public function getAccessors(): array
    {
        $componentName = $this->_table->getComponentName();
        return isset(self::$_customAccessors[$componentName]) ? self::$_customAccessors[$componentName] : [];
    }

    /**
     * sets a fieldname to have a custom mutator or check if a field has a custom
     * mutator defined (when called without the $mutator parameter)
     */
    public function hasMutator(string $fieldName, ?string $mutator = null): ?bool
    {
        $componentName = $this->_table->getComponentName();
        if ($mutator) {
            self::$_customMutators[$componentName][$fieldName] = $mutator;
        } else {
            return (isset(self::$_customMutators[$componentName][$fieldName]) && self::$_customMutators[$componentName][$fieldName]);
        }

        return null;
    }

    /**
     * gets the custom mutator for a field name
     */
    public function getMutator(string $fieldName): ?string
    {
        if ($this->hasMutator($fieldName)) {
            $componentName = $this->_table->getComponentName();
            return self::$_customMutators[$componentName][$fieldName];
        }

        return null;
    }

    /**
     * clears the custom mutator for a field name
     */
    public function clearMutator(string $fieldName): void
    {
        $componentName = $this->_table->getComponentName();
        unset(self::$_customMutators[$componentName][$fieldName]);
    }

    /**
     * gets all custom mutators for this component instance
     */
    public function getMutators(): array
    {
        $componentName = $this->_table->getComponentName();
        return self::$_customMutators[$componentName];
    }

    /**
     * Set a fieldname to have a custom accessor and mutator
     */
    public function hasAccessorMutator(string $fieldName, ?string $accessor, ?string $mutator): void
    {
        $this->hasAccessor($fieldName, $accessor);
        $this->hasMutator($fieldName, $mutator);
    }

    /**
     * returns a value of a property or a related component
     *
     * @param  string  $fieldName name of the property or related component
     * @param  boolean $load      whether or not to invoke the loading procedure
     * @throws Record\Exception        if trying to get a value of unknown property / related component
     */
    public function get(string $fieldName, bool $load = true, bool $accessors = false): mixed
    {
        static $inAccessor = [];

        if (empty($inAccessor[$fieldName]) && $accessors
        && ($this->_table->getAutoAccessorOverride() || $this->hasAccessor($fieldName))) {
            $componentName = $this->_table->getComponentName();
            $accessor = $this->getAccessor($fieldName) ?? 'get' . Inflector::classify($fieldName);

            if ($this->hasAccessor($fieldName) || method_exists($this, $accessor)) {
                $this->hasAccessor($fieldName, $accessor);
                $inAccessor[$fieldName] = true;
                try {
                    return $this->$accessor($load, $fieldName);
                } finally {
                    unset($inAccessor[$fieldName]);
                }
            }
        }

        // old _get
        $value = None::instance();

        if (array_key_exists($fieldName, $this->_values)) {
            return $this->_values[$fieldName];
        }

        if (array_key_exists($fieldName, $this->_data)) {
            if ($this->_data[$fieldName] instanceof None && $load) {
                $this->load();
            }

            if ($this->_data[$fieldName] instanceof None) {
                $value = null;
            } else {
                $value = $this->_data[$fieldName];
            }

            return $value;
        }

        try {
            if (!isset($this->_references[$fieldName])) {
                if ($load) {
                    $rel = $this->_table->getRelation($fieldName);
                    $this->_references[$fieldName] = $rel->fetchRelatedFor($this);
                } else {
                    $this->_references[$fieldName] = null;
                }
            }

            if ($this->_references[$fieldName] instanceof None) {
                return null;
            }

            return $this->_references[$fieldName];
        } catch (Table\Exception $e) {
            $success = false;
            foreach ($this->_table->getFilters() as $filter) {
                try {
                    $value = $filter->filterGet($this, $fieldName);
                    $success = true;
                } catch (Exception $e) {
                }
            }
            if (!$success) {
                throw $e;
            }

            return $value;
        }
    }

    /**
     * alters mapped values, properties and related components.
     *
     * @param string  $fieldName name of the property or reference
     * @param mixed   $value     value of the property or reference
     * @param boolean $load      whether or not to refresh / load the uninitialized record data
     *
     * @throws Record\Exception    if trying to set a value for unknown property / related component
     * @throws Record\Exception    if trying to set a value of wrong type for related component
     * @return $this
     */
    public function set(string $fieldName, mixed $value, bool $load = true, bool $mutators = false): self
    {
        if ($mutators) {
            $deserializers = $this->getDeserializers();
            if (!empty($deserializers)) {
                $value = $this->_table->deserializeColumnValue($value, $fieldName, $deserializers);
            }

            static $inMutator = [];

            if (empty($inMutator[$fieldName]) && ($this->_table->getAutoAccessorOverride() || $this->hasMutator($fieldName))) {
                $componentName = $this->_table->getComponentName();
                $mutator       = $this->getMutator($fieldName) ?? 'set' . Inflector::classify($fieldName);

                if ($this->hasMutator($fieldName) || method_exists($this, $mutator)) {
                    $this->hasMutator($fieldName, $mutator);
                    $inMutator[$fieldName] = true;
                    try {
                        $this->$mutator($value, $load, $fieldName);
                        return $this;
                    } finally {
                        unset($inMutator[$fieldName]);
                    }
                }
            }
        }

        // old _set
        if (array_key_exists($fieldName, $this->_values)) {
            $this->_values[$fieldName] = $value;
        } elseif (array_key_exists($fieldName, $this->_data)) {
            $column = $this->_table->getDefinitionOf($fieldName);
            assert($column !== null);

            if ($column->virtual) {
                return $this;
            }

            if ($value instanceof Record && $column->type !== Type::Object) {
                $id = $value->getIncremented();

                if ($id !== null) {
                    $value = $id;
                }
            }

            if ($load) {
                $old = $this->get($fieldName, $load);
            } else {
                $old = $this->_data[$fieldName];
            }

            if ($this->isValueModified($column, $old, $value)) {
                if ($value === null) {
                    $value = $this->_table->getDefaultValueOf($fieldName);
                }
                $this->_data[$fieldName] = $value;
                $this->_modified[] = $fieldName;
                $this->_oldValues[$fieldName] = $old;

                switch ($this->_state) {
                    case State::CLEAN:
                    case State::PROXY:
                        $this->_state = State::DIRTY;
                        break;
                    case State::TCLEAN:
                        $this->_state = State::TDIRTY;
                        break;
                }
            }
        } else {
            try {
                $this->coreSetRelated($fieldName, $value);
            } catch (Table\Exception|\TypeError $e) {
                $success = false;
                foreach ($this->_table->getFilters() as $filter) {
                    try {
                        $value = $filter->filterSet($this, $fieldName, $value);
                        $success = true;
                    } catch (Exception $e) {
                    }
                }
                if (!$success) {
                    throw $e;
                }
            }
        }

        return $this;
    }

    public function __set(string $name, mixed $value): void
    {
        $this->set($name, $value, mutators: true);
    }

    public function __get(string $name): mixed
    {
        return $this->get($name, accessors: true);
    }

    public function __isset(string $name): bool
    {
        return $this->contains($name);
    }

    /**
     * deletes a column or a related component.
     */
    public function __unset(string $name): void
    {
        if (array_key_exists($name, $this->_data)) {
            $this->_data[$name] = [];
        } elseif (isset($this->_references[$name])) {
            if ($this->_references[$name] instanceof Record) {
                $this->_pendingDeletes[]  = $this->$name;
                $this->_references[$name] = None::instance();
            } elseif ($this->_references[$name] instanceof Collection) {
                $this->_pendingDeletes[] = $this->$name;
                $this->_references[$name]->setData([]);
            }
        }
    }

    public function offsetExists($offset): bool
    {
        return $this->contains($offset);
    }

    public function offsetGet($offset): mixed
    {
        if (!is_string($offset)) {
            return null;
        }
        return $this->__get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        if (!is_string($offset)) {
            return;
        }
        $this->__set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        if (!is_string($offset)) {
            return;
        }
        $this->__unset($offset);
    }

    /**
     * sets a value that will be managed as if it were a field by magic accessor and mutators, @see get() and @see set().
     * Normally used by Doctrine for the mapping of aggregate values.
     *
     * @param  string $name  the name of the mapped value
     * @param  mixed  $value mixed value to be mapped
     */
    public function mapValue(string $name, mixed $value = null): void
    {
        $this->_values[$name] = $value;
    }

    /**
     * Tests whether a mapped value exists
     *
     * @param  string $name the name of the property
     */
    public function hasMappedValue(string $name): bool
    {
        return array_key_exists($name, $this->_values);
    }

    /**
     * Check if a value has changed according to Doctrine
     * Doctrine is loose with type checking in the same ways PHP is for consistancy of behavior
     *
     * This function basically says if what is being set is of Doctrine type boolean and something
     * like current_value == 1 && new_value = true would not be considered modified
     *
     * Simply doing $old !== $new will return false for boolean columns would mark the field as modified
     * and change it in the database when it is not necessary
     *
     * @return bool Whether or not Doctrine considers the value modified
     */
    protected function isValueModified(Column $column, mixed $old, mixed $new): bool
    {
        if ($new instanceof Expression) {
            return true;
        }

        if ($new instanceof None || $new === null) {
            return !($old instanceof None) && $old !== null;
        }

        $serializers = $this->getSerializers();
        foreach ($serializers as $serializer) {
            try {
                return !$serializer->areEquivalent($old, $new, $column, $this->_table);
            } catch (Serializer\Exception\Incompatible) {
            }
        }

        if (!is_scalar($old) || !is_scalar($new)) {
            return $old !== $new;
        }

        if (is_numeric($old) && is_numeric($new)) {
            if (in_array($column->type, [Type::Decimal, Type::Float], true)) {
                return $old * 100 != $new * 100;
            }

            if ($column->type === Type::Integer) {
                return $old != $new;
            }
        }

        if (in_array($column->type, [Type::Timestamp, Type::Date, Type::DateTime], true)) {
            $oldStrToTime = strtotime((string) $old);
            if ($oldStrToTime) {
                return $oldStrToTime !== strtotime((string) $new);
            }
            return $old !== $new;
        }

        return $old !== $new;
    }

    /**
     * Places a related component in the object graph.
     *
     * This method inserts a related component instance in this record
     * relations, populating the foreign keys accordingly.
     *
     * @param  string $name  related component alias in the relation
     * @param  Record|Collection|None|null $value object to be linked as a related component
     * @return $this|null
     *
     * @todo Refactor. What about composite keys?
     */
    public function coreSetRelated(string $name, Record|Collection|None|null $value): ?self
    {
        $rel = $this->_table->getRelation($name);

        if ($value === null) {
            $value = None::instance();
        }

        // one-to-many or one-to-one relation
        if ($rel instanceof Relation\ForeignKey || $rel instanceof Relation\LocalKey) {
            if (!$rel->isOneToOne()) {
                // one-to-many relation found
                if (!$value instanceof Collection) {
                    throw new Record\Exception("Couldn't call Core::set(), second argument should be an instance of Collection when setting one-to-many references.");
                }

                if (isset($this->_references[$name]) && $this->_references[$name] instanceof Collection) {
                    $this->_references[$name]->setData($value->getData());
                    return $this;
                }
            } else {
                $localFieldName   = $this->_table->getFieldName($rel->getLocal());
                $foreignFieldName = '';

                if (!$value instanceof None) {
                    $relatedTable     = $rel->getTable();
                    $foreignFieldName = $relatedTable->getFieldName($rel->getForeign());
                }

                // one-to-one relation found
                if (!$value instanceof Record && !$value instanceof None) {
                    throw new Record\Exception("Couldn't call Core::set(), second argument should be an instance of Record or None when setting one-to-one references.");
                }

                if ($rel instanceof Relation\LocalKey) {
                    if (!$value instanceof None && !empty($foreignFieldName) && $foreignFieldName != $value->getTable()->getIdentifier()) {
                        $this->set($localFieldName, $value->rawGet($foreignFieldName), false, mutators: true);
                    } else {
                        // FIX: Ticket #1280 fits in this situation
                        $this->set($localFieldName, $value, false, mutators: true);
                    }
                } elseif (!$value instanceof None) {
                    // We should only be able to reach $foreignFieldName if we have a Record on hands
                    $value->set($foreignFieldName, $this, false);
                }
            }
        } elseif ($rel instanceof Relation\Association) {
            // join table relation found
            if (!($value instanceof Collection)) {
                throw new Record\Exception("Couldn't call Core::set(), second argument should be an instance of Collection when setting many-to-many references.");
            }
        }

        $this->_references[$name] = $value;

        return null;
    }

    /**
     * test whether a field (column, mapped value, related component, accessor) is accessible by @see get()
     */
    public function contains(string $fieldName, bool $load = true): bool
    {
        if (array_key_exists($fieldName, $this->_data)
            || isset($this->_id[$fieldName])
            || isset($this->_values[$fieldName])
            || ($this->hasReference($fieldName) && !$this->_references[$fieldName] instanceof None)
        ) {
            return true;
        }

        if (!$load) {
            return false;
        }

        try {
            $this->_table->getRelation($fieldName);
            return $this->relatedExists($fieldName);
        } catch (Table\Exception) {
            return false;
        }
    }

    /**
     * returns Record instances which need to be deleted on save
     */
    public function getPendingDeletes(): array
    {
        return $this->_pendingDeletes;
    }

    /**
     * returns Record instances which need to be unlinked (deleting the relation) on save
     */
    public function getPendingUnlinks(): array
    {
        return $this->_pendingUnlinks;
    }

    /**
     * resets pending record unlinks
     */
    public function resetPendingUnlinks(): void
    {
        $this->_pendingUnlinks = [];
    }

    /**
     * applies the changes made to this object into database
     * this method is smart enough to know if any changes are made
     * and whether to use INSERT or UPDATE statement
     *
     * this method also saves the related components
     *
     * @param  Connection $conn optional connection parameter
     * @throws Exception                    if record is not valid and validation is active
     * @throws Validator\Exception if record is not valid and validation is active
     */
    public function save(?Connection $conn = null): void
    {
        if ($conn === null) {
            $conn = $this->_table->getConnection();
        }
        $conn->unitOfWork->saveGraph($this);
    }

    /**
     * tries to save the object and all its related components.
     * In contrast to Record::save(), this method does not
     * throw an exception when validation fails but returns TRUE on
     * success or FALSE on failure.
     *
     * @param  Connection $conn optional connection parameter
     * @return bool TRUE if the record was saved sucessfully without errors, FALSE otherwise.
     */
    public function trySave(?Connection $conn = null): bool
    {
        try {
            $this->save($conn);
            return true;
        } catch (Validator\Exception $ignored) {
            return false;
        }
    }

    /**
     * executes a SQL REPLACE query. A REPLACE query is identical to a INSERT
     * query, except that if there is already a row in the table with the same
     * key field values, the REPLACE query just updates its values instead of
     * inserting a new row.
     *
     * The REPLACE type of query does not make part of the SQL standards. Since
     * practically only MySQL and SQLIte implement it natively, this type of
     * query isemulated through this method for other DBMS using standard types
     * of queries inside a transaction to assure the atomicity of the operation.
     *
     * @param  Connection $conn optional connection parameter
     * @throws Connection\Exception        if some of the key values was null
     * @throws Connection\Exception        if there were no key fields
     * @throws Connection\Exception        if something fails at database level
     */
    public function replace(?Connection $conn = null): bool
    {
        if ($conn === null) {
            $conn = $this->_table->getConnection();
        }
        return $conn->unitOfWork->saveGraph($this, true);
    }

    /**
     * retrieves an array of modified fields and associated new values.
     *
     * @param          boolean $old  pick the old values (instead of the new ones)
     * @param          boolean $last pick only lastModified values (@see getLastModified())
     * @phpstan-return array<string, mixed>
     */
    public function getModified(bool $old = false, bool $last = false): array
    {
        $a = [];
        $modified = $last ? $this->_lastModified : $this->_modified;
        foreach ($modified as $fieldName) {
            $column = $this->_table->getDefinitionOf($fieldName);
            assert($column !== null);

            // ignore virtual columns
            if ($column->virtual) {
                continue;
            }

            if ($old) {
                $oldValue = isset($this->_oldValues[$fieldName])
                    ? $this->_oldValues[$fieldName]
                    : $this->_table->getDefaultValueOf($fieldName);
                $curValue = $this->get($fieldName, false);

                if ($this->isValueModified($column, $oldValue, $curValue)) {
                    $a[$fieldName] = $oldValue instanceof None ? null : $oldValue;
                }
            } else {
                $a[$fieldName] = $this->_data[$fieldName];
            }
        }
        return $a;
    }

    /**
     * returns an array of the modified fields from the last transaction.
     *
     * @param  boolean $old pick the old values (instead of the new ones)
     */
    public function getLastModified(bool $old = false): array
    {
        return $this->getModified($old, true);
    }

    /** @return Deserializer\DeserializerInterface[] */
    public function getDeserializers(): array
    {
        return array_merge(
            Manager::getInstance()->getDeserializers(),
            $this->_table->getDeserializers(),
        );
    }

    /** @return Serializer\SerializerInterface[] */
    public function getSerializers(): array
    {
        return array_merge(
            Manager::getInstance()->getSerializers(),
            $this->_table->getSerializers(),
        );
    }

    /**
     * Retrieves data prepared for a sql transaction.
     *
     * Returns an array of modified fields and values with data preparation;
     * adds column aggregation inheritance and converts Records into primary
     * key values.
     *
     * @todo   What about a little bit more expressive name? getPreparedData?
     */
    public function getPrepared(): array
    {
        $prepared = [];
        $modifiedFields = $this->_modified;

        foreach ($modifiedFields as $field) {
            $column = $this->_table->getDefinitionOf($field);
            assert($column !== null);

            // skip virtual columns
            if ($column->virtual) {
                continue;
            }

            $dataValue = $this->_data[$field];

            if ($dataValue instanceof None) {
                $prepared[$field] = null;
                continue;
            }

            // we cannot use serializers in this case but it should be fine
            if ($dataValue instanceof Record && $column->type !== Type::Object) {
                $prepared[$field] = $dataValue->getIncremented();
                if ($prepared[$field] !== null) {
                    $this->_data[$field] = $prepared[$field];
                }
                continue;
            }

            $prepared[$field] = $this->_table->serializeColumnValue($dataValue, $column->fieldName);
        }

        return $prepared;
    }

    /**
     * implements Countable interface
     *
     * @return integer the number of columns in this record
     */
    public function count(): int
    {
        return count($this->_data);
    }

    /**
     * alias for @see count()
     */
    public function columnCount(): int
    {
        return $this->count();
    }

    /**
     * returns the record representation as an array
     *
     * @param boolean $deep      whether to include relations
     * @param boolean $prefixKey not used
     * @phpstan-return array<string, mixed>|null
     */
    public function toArray(bool $deep = true, bool $prefixKey = false): ?array
    {
        if ($this->_state->isLocked()) {
            return null;
        }

        $a = [];

        $stateBeforeLock = $this->_state;
        $this->_state = $this->_state->lock();

        try {
            foreach ($this as $column => $value) {
                if ($value instanceof None || is_object($value)) {
                    $value = null;
                }

                $columnValue = $this->get($column, false, accessors: true);

                if ($columnValue instanceof Record) {
                    $a[$column] = $columnValue->getIncremented();
                } else {
                    $a[$column] = $columnValue;
                }
            }

            if ($this->_table->getIdentifierType() === IdentifierType::Autoinc) {
                $i = $this->_table->getIdentifier();
                if (is_array($i)) {
                    throw new Exception("Multi column identifiers are not supported for auto increments");
                }
                $a[$i] = $this->getIncremented();
            }

            if ($deep) {
                foreach ($this->_references as $key => $relation) {
                    if ($relation !== null && !$relation instanceof None) {
                        $a[$key] = $relation->toArray($deep, $prefixKey);
                    }
                }
            }

            // [FIX] Prevent mapped Records from being displayed fully
            foreach ($this->_values as $key => $value) {
                $a[$key] = ($value instanceof Record || $value instanceof Collection)
                    ? $value->toArray($deep, $prefixKey) : $value;
            }
        } finally {
            $this->_state = $stateBeforeLock;
        }

        return $a;
    }

    /**
     * merges this record with an array of values
     * or with another existing instance of this object
     *
     * @see    fromArray()
     * @link   http://www.doctrine-project.org/documentation/manual/1_1/en/working-with-models
     * @param  array|Record $data array of data to merge, see link for documentation
     * @param  bool                  $deep whether or not to merge relations
     */
    public function merge(array|Record $data, bool $deep = true): void
    {
        if ($data instanceof $this) {
            $array = $data->toArray($deep) ?: [];
        } elseif (is_array($data)) {
            $array = $data;
        } else {
            $array = [];
        }

        $this->fromArray($array, $deep);
    }

    /**
     * imports data from a php array
     *
     * @link   http://www.doctrine-project.org/documentation/manual/1_1/en/working-with-models
     * @param  array $array array of data, see link for documentation
     * @param  bool  $deep  whether or not to act on relations
     */
    public function fromArray(array $array, bool $deep = true): void
    {
        $refresh = false;
        foreach ($array as $key => $value) {
            if ($key == '_identifier') {
                $refresh = true;
                $this->assignIdentifier($value);
                continue;
            }

            if ($deep && $this->getTable()->hasRelation($key)) {
                if (!$this->$key) {
                    $this->refreshRelated($key);
                }

                if (is_array($value)) {
                    if (isset($value[0]) && !is_array($value[0])) {
                        $this->unlink($key, [], false);
                        $this->link($key, $value, false);
                    } else {
                        if ($this->$key === null) {
                            $rel = $this->getTable()->getRelation($key);
                            $this->$key = $rel->getTable()->create();
                        }
                        $this->$key->fromArray($value, $deep);
                    }
                }
            } elseif ($this->getTable()->hasField($key) || array_key_exists($key, $this->_values)) {
                $this->set($key, $value, mutators: true);
            } else {
                $method = 'set' . Inflector::classify($key);

                try {
                    if (is_callable([$this, $method])) {
                        $this->$method($value);
                    }
                } catch (Record\Exception $e) {
                }
            }
        }

        if ($refresh) {
            $this->refresh();
        }
    }

    /**
     * synchronizes a Record instance and its relations with data from an array
     *
     * it expects an array representation of a Record similar to the return
     * value of the toArray() method. If the array contains relations it will create
     * those that don't exist, update the ones that do, and delete the ones missing
     * on the array but available on the Record (unlike @see fromArray() that
     * does not touch what it is not in $array)
     *
     * @param array $array representation of a Record
     * @param bool  $deep  whether or not to act on relations
     */
    public function synchronizeWithArray(array $array, bool $deep = true): void
    {
        $refresh = false;
        foreach ($array as $key => $value) {
            if ($key == '_identifier') {
                $refresh = true;
                $this->assignIdentifier($value);
                continue;
            }

            if ($deep && $this->getTable()->hasRelation($key)) {
                if (!$this->$key) {
                    $this->refreshRelated($key);
                }

                if (is_array($value)) {
                    if (isset($value[0]) && !is_array($value[0])) {
                        $this->unlink($key, [], false);
                        $this->link($key, $value, false);
                    } else {
                        if ($this->$key === null) {
                            $rel = $this->getTable()->getRelation($key);
                            $this->$key = $rel->getTable()->create();
                        }
                        $this->$key->synchronizeWithArray($value);
                        $this->$key = $this->$key;
                    }
                }
            } elseif ($this->getTable()->hasField($key) || array_key_exists($key, $this->_values)) {
                $this->set($key, $value);
            }
        }

        // Eliminate relationships missing in the $array
        foreach ($this->_references as $name => $relation) {
            $rel = $this->getTable()->getRelation($name);

            if (!$rel->isRefClass() && !isset($array[$name]) && (!$rel->isOneToOne() || !isset($array[$rel->getLocalFieldName()]))) {
                unset($this->$name);
            }
        }

        if ($refresh) {
            $this->refresh();
        }
    }

    /**
     * returns true if this record is saved in the database, otherwise false (it is transient)
     */
    public function exists(): bool
    {
        return !$this->_state->isTransient();
    }

    /**
     * returns true if this record was modified, otherwise false
     *
     * @param  boolean $deep whether to process also the relations for changes
     */
    public function isModified(bool $deep = false): bool
    {
        $modified = $this->_state->isDirty();
        if (!$modified && $deep) {
            if ($this->_state->isLocked()) {
                return false;
            }

            $stateBeforeLock = $this->_state;
            try {
                $this->_state = $this->_state->lock();

                foreach ($this->_references as $reference) {
                    if ($reference instanceof Record) {
                        if ($modified = $reference->isModified($deep)) {
                            break;
                        }
                    } elseif ($reference instanceof Collection) {
                        foreach ($reference as $record) {
                            if ($modified = $record->isModified($deep)) {
                                break 2;
                            }
                        }
                    }
                }
            } finally {
                $this->_state = $stateBeforeLock;
            }
        }
        return $modified;
    }

    /**
     * checks existence of properties and related components
     *
     * @param  string $fieldName name of the property or reference
     */
    public function hasRelation(string $fieldName): bool
    {
        if (isset($this->_data[$fieldName]) || isset($this->_id[$fieldName])) {
            return true;
        }
        return $this->_table->hasRelation($fieldName);
    }

    /**
     * implements IteratorAggregate interface
     *
     * @return ArrayIterator<string, mixed> iterator through data
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->getData());
    }

    /**
     * deletes this data access object and all the related composites
     * this operation is isolated by a transaction
     *
     * this event can be listened by the onPreDelete and onDelete listeners
     */
    public function delete(?Connection $conn = null): void
    {
        if ($conn == null) {
            $conn = $this->_table->getConnection();
        }
        $conn->unitOfWork->delete($this);
    }

    /**
     * generates a copy of this object. Returns an instance of the same class of $this.
     *
     * @param  boolean $deep whether to duplicates the objects targeted by the relations
     * @return static
     */
    public function copy(bool $deep = false): self
    {
        $data   = $this->_data;
        $idtype = $this->_table->getIdentifierType();
        if ($idtype === IdentifierType::Autoinc || $idtype === IdentifierType::Sequence) {
            $id = $this->_table->getIdentifier();
            if (is_scalar($id)) {
                unset($data[$id]);
            }
        }

        /** @var static */
        $ret = $this->_table->create($data);

        foreach ($data as $key => $val) {
            if (!($val instanceof None)) {
                $ret->_modified[] = $key;
            }
        }

        if ($deep) {
            foreach ($this->_references as $key => $value) {
                if ($value instanceof Collection) {
                    foreach ($value as $valueKey => $record) {
                        $ret->{$key}[$valueKey] = $record->copy($deep);
                    }
                } elseif ($value instanceof Record) {
                    $ret->set($key, $value->copy($deep));
                }
            }
        }

        return $ret;
    }

    /**
     * assigns an identifier to the instance, for database storage
     *
     * @param  scalar|array<string, scalar> $id a key value or an array of keys
     */
    public function assignIdentifier(mixed $id = false): void
    {
        if ($id === false) {
            $this->_id = [];
            $this->_data = $this->cleanData($this->_data, deserialize: false);
            $this->_state = State::TCLEAN;
            $this->resetModified();
        } elseif ($id === true) {
            $this->prepareIdentifiers(true);
            $this->_state = State::CLEAN;
            $this->resetModified();
        } else {
            if (is_array($id)) {
                foreach ($id as $fieldName => $value) {
                    $this->_id[$fieldName] = $value;
                    $this->_data[$fieldName] = $value;
                }
            } else {
                $name = $this->_table->getIdentifier();
                assert(!is_array($name));
                $this->_id[$name] = $id;
                $this->_data[$name] = $id;
            }
            $this->_state = State::CLEAN;
            $this->resetModified();
        }
    }

    /**
     * returns the primary keys of this object
     */
    public function identifier(): array
    {
        return $this->_id;
    }

    /**
     * returns the value of autoincremented primary key of this object (if any)
     * @todo   Better name?
     */
    final public function getIncremented(): int|string|null
    {
        $id = current($this->_id);
        if ($id === false) {
            return null;
        }

        return $id;
    }

    /**
     * this method is used internally by Query
     * it is needed to provide compatibility between
     * records and collections
     *
     * @return $this
     */
    public function getLast(): self
    {
        return $this;
    }

    /**
     * tests whether a relation is set
     *
     * @param  string $name relation alias
     */
    public function hasReference(string $name): bool
    {
        return isset($this->_references[$name]);
    }

    /**
     * gets a related component
     */
    public function reference(string $name): Record|Collection|null
    {
        if (isset($this->_references[$name]) && !$this->_references[$name] instanceof None) {
            return $this->_references[$name];
        }

        return null;
    }

    /**
     * gets a related component and fails if it does not exist
     *
     * @throws Record\Exception        if trying to get an unknown related component
     */
    public function obtainReference(string $name): Record|Collection
    {
        if (isset($this->_references[$name]) && !$this->_references[$name] instanceof None) {
            return $this->_references[$name];
        }
        throw new Record\Exception("Unknown reference $name");
    }

    /**
     * get all related components
     *
     * @phpstan-return array<string, Record|Collection|null|None>
     * @return array    various Collection or Record instances
     */
    public function getReferences(): array
    {
        return $this->_references;
    }

    /**
     * set a related component
     */
    final public function setRelated(string $alias, Record|Collection $coll): void
    {
        $this->_references[$alias] = $coll;
    }

    /**
     * loads a related component
     *
     * @throws Table\Exception             if trying to load an unknown related component
     * @param  string $name alias of the relation
     */
    public function loadReference(string $name): void
    {
        $rel = $this->_table->getRelation($name);
        $this->_references[$name] = $rel->fetchRelatedFor($this);
    }

    /**
     * @param  callable $callback valid callback
     * @param  string   $column   column name
     * @param  mixed    ...$args  optional callback arguments
     * @return $this
     */
    public function call(callable $callback, string $column, mixed ...$args): self
    {
        // Put $column on front of $args to maintain previous behavior
        array_unshift($args, $column);

        if (isset($args[0]) && is_string($args[0])) {
            $fieldName = $args[0];
            $args[0]   = $this->get($fieldName, accessors: true);

            $newvalue = call_user_func_array($callback, $args);

            $this->_data[$fieldName] = $newvalue;
        }
        return $this;
    }

    /**
     * @phpstan-return T
     */
    public function unshiftFilter(Record\Filter $filter): Table
    {
        return $this->_table->unshiftFilter($filter);
    }

    /**
     * removes links from this record to given records
     * if no ids are given, it removes all links
     *
     * @param  string  $alias related component alias
     * @param  array|string|int   $ids   the identifiers of the related records
     * @param  boolean $now   whether or not to execute now or set as pending unlinks
     * @return $this
     */
    public function unlink(string $alias, array|string|int $ids = [], bool $now = false): self
    {
        $ids = (array) $ids;

        // fix for #1622
        if (!isset($this->_references[$alias]) && $this->hasRelation($alias)) {
            $this->loadReference($alias);
        }

        $allIds = [];
        if (isset($this->_references[$alias]) && !$this->_references[$alias] instanceof None) {
            if ($this->_references[$alias] instanceof Record) {
                $allIds[] = $this->_references[$alias]->identifier();
                if (in_array($this->_references[$alias]->identifier(), $ids) || empty($ids)) {
                    unset($this->_references[$alias]);
                }
            } else {
                $allIds = $this->get($alias, accessors: true)->getPrimaryKeys();
                foreach ($this->_references[$alias] as $k => $record) {
                    if (in_array(current($record->identifier()), $ids) || empty($ids)) {
                        $this->_references[$alias]->remove($k);
                    }
                }
            }
        }

        if (!$this->exists() || $now === false) {
            if (!$ids) {
                $ids = $allIds;
            }
            foreach ($ids as $id) {
                $this->_pendingUnlinks[$alias][$id] = true;
            }
            return $this;
        } else {
            return $this->unlinkInDb($alias, $ids);
        }
    }

    /**
     * unlink now the related components, querying the db
     *
     * @param  string $alias related component alias
     * @param  array  $ids   the identifiers of the related records
     * @return $this
     */
    public function unlinkInDb(string $alias, array $ids = []): self
    {
        $rel = $this->getTable()->getRelation($alias);

        if ($rel instanceof Relation\Association) {
            $q = $rel->getAssociationTable()
                ->createQuery()
                ->delete()
                ->where($rel->getLocal() . ' = ?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $q->whereIn($rel->getForeign(), $ids);
            }

            $q->execute();
        } elseif ($rel instanceof Relation\ForeignKey) {
            $q = $rel->getTable()->createQuery()
                ->update()
                ->set($rel->getForeign(), '?', [null])
                ->addWhere($rel->getForeign() . ' = ?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $identifier = $rel->getTable()->getIdentifier();
                if (is_array($identifier)) {
                    throw new Exception("Cannot unlink related components with multi-column identifiers");
                }
                $q->whereIn($identifier, $ids);
            }

            $q->execute();
        }
        return $this;
    }

    /**
     * creates links from this record to given records
     *
     * @param  string  $alias related component alias
     * @param  array|string|int   $ids   the identifiers of the related records
     * @param  boolean $now   wether or not to execute now or set pending
     * @return $this
     */
    public function link(string $alias, array|string|int $ids, bool $now = false): self
    {
        $ids = (array) $ids;

        if (!count($ids)) {
            return $this;
        }

        if (!$this->exists() || $now === false) {
            $relTable = $this->getTable()->getRelation($alias)->getTable();

            $identifier = $relTable->getIdentifier();
            if (is_array($identifier)) {
                throw new Exception("Cannot link related components with multi-column identifiers");
            }

            /** @phpstan-var Collection<static> */
            $records = $relTable->createQuery()
                ->whereIn($identifier, $ids)
                ->execute();

            foreach ($records as $record) {
                if ($this->$alias instanceof Record) {
                    $this->set($alias, $record, mutators: true);
                } else {
                    if ($c = $this->get($alias, accessors: true)) {
                        $c->add($record);
                    } else {
                        $this->set($alias, $record, mutators: true);
                    }
                }
            }

            foreach ($ids as $id) {
                if (isset($this->_pendingUnlinks[$alias][$id])) {
                    unset($this->_pendingUnlinks[$alias][$id]);
                }
            }

            return $this;
        } else {
            return $this->linkInDb($alias, $ids);
        }
    }

    /**
     * creates links from this record to given records now, querying the db
     *
     * @param  string $alias related component alias
     * @param  array  $ids   the identifiers of the related records
     * @return $this
     */
    public function linkInDb(string $alias, array $ids): self
    {
        $identifier = array_values($this->identifier());
        $identifier = array_shift($identifier);

        $rel = $this->getTable()->getRelation($alias);

        if ($rel instanceof Relation\Association) {
            $modelClassName = $rel->getAssociationTable()->getComponentName();
            $localFieldName = $rel->getLocalFieldName();
            $localFieldDef  = $rel->getAssociationTable()->getColumn($localFieldName);
            assert($localFieldDef !== null);

            if ($localFieldDef->type === Type::Integer) {
                $identifier = (int) $identifier;
            }

            $foreignFieldName = $rel->getForeignFieldName();
            $foreignFieldDef  = $rel->getAssociationTable()->getColumn($foreignFieldName);
            assert($foreignFieldDef !== null);

            if ($foreignFieldDef->type === Type::Integer) {
                foreach ($ids as $i => $id) {
                    $ids[$i] = (int) $id;
                }
            }

            foreach ($ids as $id) {
                /** @var Record $record */
                $record                    = new $modelClassName();
                $record[$localFieldName]   = $identifier;
                $record[$foreignFieldName] = $id;
                $record->save();
            }
        } elseif ($rel instanceof Relation\ForeignKey) {
            $q = $rel->getTable()
                ->createQuery()
                ->update()
                ->set($rel->getForeign(), '?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $identifier = $rel->getTable()->getIdentifier();
                if (is_array($identifier)) {
                    throw new Exception("Cannot link related components with multi-column identifiers");
                }
                $q->whereIn($identifier, $ids);
            }

            $q->execute();
        } elseif ($rel instanceof Relation\LocalKey) {
            $q = $this->getTable()
                ->createQuery()
                ->update()
                ->set($rel->getLocalFieldName(), '?', $ids);

            if (count($ids) > 0) {
                $identifier = $rel->getTable()->getIdentifier();
                if (is_array($identifier)) {
                    throw new Exception("Cannot link related components with multi-column identifiers");
                }
                $q->whereIn($identifier, array_values($this->identifier()));
            }

            $q->execute();
        }

        return $this;
    }

    /**
     * Reset the modified array and store the old array in lastModified so it
     * can be accessed by users after saving a record, since the modified array
     * is reset after the object is saved.
     */
    protected function resetModified(): void
    {
        if (!empty($this->_modified)) {
            $this->_lastModified = $this->_modified;
            $this->_modified = [];
        }
    }

    /**
     * Helps freeing the memory occupied by the entity.
     * Cuts all references the entity has to other entities and removes the entity
     * from the instance pool.
     * Note: The entity is no longer useable after free() has been called. Any operations
     * done with the entity afterwards can lead to unpredictable results.
     *
     * @param boolean $deep whether to free also the related components
     */
    public function free(bool $deep = false): void
    {
        if (!$this->_state->isLocked()) {
            $this->_state = $this->_state->lock();

            $repo = $this->_table->getRepository();
            if ($repo !== null) {
                $repo->evict($this->_oid);
            }
            $this->_table->removeRecord($this);
            $this->_data = [];
            $this->_id   = [];

            if ($deep) {
                foreach ($this->_references as $name => $reference) {
                    if ($reference && !$reference instanceof None) {
                        $reference->free($deep);
                    }
                }
            }

            $this->_references = [];
        }
    }

    /**
     * @return string representation of this object
     */
    public function __toString()
    {
        return (string) $this->_oid;
    }

    /**
     * @phpstan-param Record\ListenerInterface|Overloadable<Record\ListenerInterface> $listener
     * @return $this
     */
    public function addListener(Record\ListenerInterface|Overloadable $listener, ?string $name = null): self
    {
        $this->_table->addRecordListener($listener, $name);
        return $this;
    }

    /**
     * @phpstan-return Record\ListenerInterface|Overloadable<Record\ListenerInterface>|null
     */
    public function getListener(): Record\ListenerInterface|Overloadable|null
    {
        return $this->_table->getRecordListener();
    }

    /**
     * @phpstan-param Record\ListenerInterface|Overloadable<Record\ListenerInterface> $listener
     * @return $this
     */
    public function setListener(Record\ListenerInterface|Overloadable $listener): self
    {
        $this->_table->setRecordListener($listener);
        return $this;
    }

    /**
     * defines or retrieves an index
     * if the second parameter is set this method defines an index
     * if not this method retrieves index named $name
     *
     * @param string $name       the name of the index
     * @param array $definition the definition array
     * @phpstan-param array{type?: string, fields?: string[]} $definition
     * @return mixed[]|null
     */
    public function index(string $name, array $definition = []): ?array
    {
        if (!isset($definition['fields'])) {
            return $this->_table->getIndex($name);
        }
        $this->_table->addIndex($name, $definition);
        return null;
    }

    /**
     * Defines a n-uple of fields that must be unique for every record.
     *
     * This method Will automatically add UNIQUE index definition
     * and validate the values on save. The UNIQUE index is not created in the
     * database until you use @see export().
     *
     * @param  string[] $fields            values are fieldnames
     * @param  mixed[] $options           array of options for unique validator
     * @param  bool  $createUniqueIndex Whether or not to create a unique index in the database
     */
    public function unique(array $fields, array $options = [], bool $createUniqueIndex = true): void
    {
        $this->_table->unique($fields, $options, $createUniqueIndex);
    }

    public function setTableName(string $tableName): void
    {
        $this->_table->setTableName($tableName);
    }

    /** @phpstan-param array<string, mixed> $map */
    public function setInheritanceMap(array $map): void
    {
        $this->_table->inheritanceMap = $map;
    }

    /** @phpstan-param array<class-string<Record>, array<string, mixed>> $map */
    public function setSubclasses(array $map): void
    {
        // Set the inheritance map for subclasses
        if (isset($map[static::class])) {
            // fix for #1621
            $mapColumnNames = [];

            foreach ($map[static::class] as $fieldName => $val) {
                $mapColumnNames[$this->getTable()->getColumnName($fieldName)] = $val;
            }

            $this->_table->inheritanceMap = $mapColumnNames;
            return;
        } else {
            // Put an index on the key column
            assert(!empty($map));
            $mapFieldName = array_keys(end($map));
            $this->index($this->getTable()->getTableName() . '_' . $mapFieldName[0], ['fields' => [$mapFieldName[0]]]);
        }

        // Set the subclasses array for the parent class
        $this->_table->subclasses = array_keys($map);
    }

    public function setCollectionKey(?string $value): void
    {
        $this->_table->setCollectionKey($value);
    }

    /**
     * Binds One-to-One aggregate relation
     *
     * @param  string|array ...$args First: the name of the related component
     *                               Second: relation options
     * @see    Relation::_$definition
     * @return $this
     */
    public function hasOne(string|array ...$args): self
    {
        $this->_table->bind($args, Relation::ONE);
        return $this;
    }

    /**
     * Binds One-to-Many / Many-to-Many aggregate relation
     *
     * @param  string|array ...$args First: the name of the related component
     *                               Second: relation options
     * @see    Relation::_$definition
     * @return $this
     */
    public function hasMany(string|array ...$args): self
    {
        $this->_table->bind($args, Relation::MANY);
        return $this;
    }

    public function setColumn(Column $column): void
    {
        $this->_table->setColumn($column);
    }

    /**
     * Sets a column definition
     * @deprecated
     *
     * @param  non-empty-string  $name
     * @param  ?positive-int $length
     */
    public function hasColumn(string $name, string $type, ?int $length = null, array|string $options = []): void
    {
        if (is_string($options)) {
            $options = [$options => true];
        }

        foreach ($options as $k => $v) {
            if (is_numeric($k)) {
                $options[$v] = true;
                unset($options[$k]);
            }
        }

        $validators = [];
        foreach (['date', 'time', 'timestamp', 'range', 'notblank', 'email', 'ip', 'regexp'] as $validatorName) {
            if (isset($options[$validatorName])) {
                $validators[$validatorName] = $options[$validatorName];
            }
        }

        $this->setColumn(new Column(
            name: $name,
            type: Type::fromNative($type),
            length: $length,
            owner: $options['owner'] ?? null,
            primary: !empty($options['primary']),
            default: $options['default'] ?? null,
            notnull: !empty($options['notnull']),
            values: isset($options['values']) && is_array($options['values']) ? $options['values'] : [],
            autoincrement: !empty($options['autoincrement']),
            unique: !empty($options['unique']),
            protected: !empty($options['protected']),
            sequence: $options['sequence'] ?? null,
            zerofill: !empty($options['zerofill']),
            unsigned: !empty($options['unsigned']),
            scale: $options['scale'] ?? 0,
            fixed: !empty($options['fixed']),
            comment: $options['comment'] ?? null,
            charset: $options['charset'] ?? null,
            collation: $options['collation'] ?? null,
            check: $options['check'] ?? null,
            min: $options['min'] ?? null,
            max: $options['max'] ?? null,
            extra: isset($options['extra']) && is_array($options['extra']) ? $options['extra'] : [],
            virtual: !empty($options['virtual']),
            meta: isset($options['meta']) && is_array($options['meta']) ? $options['meta'] : [],
            validators: $validators,
        ));
    }

    /**
     * Set multiple column definitions at once
     *
     * @param Column[] $columns
     * @phpstan-param list<Column> $columns
     */
    public function setColumns(array $columns): void
    {
        foreach ($columns as $column) {
            $this->setColumn($column);
        }
    }

    /**
     * bindQueryParts
     * binds query parts to given component
     *
     * @param  array $queryParts an array of pre-bound query parts
     * @return $this
     */
    public function bindQueryParts(array $queryParts): self
    {
        $this->_table->bindQueryParts($queryParts);

        return $this;
    }

    /**
     * Adds a check constraint.
     *
     * This method will add a CHECK constraint to the record table.
     *
     * @param  array|string|int|null  $constraint either a SQL constraint portion or an array of CHECK constraints. If array, all values will be added as constraint
     * @param  string $name       optional constraint name. Not used if $constraint is an array.
     * @return $this
     */
    public function check(array|string|int|null $constraint, ?string $name = null): self
    {
        if (is_array($constraint)) {
            foreach ($constraint as $name => $def) {
                $this->_table->addCheckConstraint($def, $name);
            }
        } else {
            $this->_table->addCheckConstraint($constraint, $name);
        }
        return $this;
    }

    /**
     * @phpstan-param array<string, mixed> $array
     * @return $this
     */
    public function setArray(array $array): self
    {
        foreach ($array as $k => $v) {
            $this->__set($k, $v);
        }
        return $this;
    }
}
