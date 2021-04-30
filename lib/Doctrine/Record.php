<?php

use Doctrine_Record_State as State;
use Doctrine1\Serializer;
use Doctrine1\Deserializer;

/**
 * @phpstan-template T of Doctrine_Table
 * @phpstan-implements ArrayAccess<string, mixed>
 */
abstract class Doctrine_Record implements Countable, IteratorAggregate, Serializable, ArrayAccess
{
    /**
     * the primary keys of this object
     */
    protected array $_id = [];

    /**
     * each element is one of 3 following types:
     * - simple type (int, string) - field has a scalar value
     * - null - field has NULL value in DB
     * - Doctrine_Null - field value is unknown, it wasn't loaded yet
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
    protected ?Doctrine_Validator_ErrorStack $_errorStack = null;

    /**
     * an array containing all the references
     */
    protected array $_references = [];

    /**
     * Doctrine_Collection of objects needing to be deleted on save
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
     * Whether or not to serialize references when a Doctrine_Record is serialized
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
     * reference to associated Doctrine_Table instance
     * @phpstan-var T
     */
    protected Doctrine_Table $_table;

    /**
     * @param Doctrine_Table|null $table a Doctrine_Table object or null,
     *                                   if null the table object is
     *                                   retrieved from current
     *                                   connection
     * @phpstan-param T|null $table
     *
     * @param boolean $isNewEntry whether or not this record is transient
     *
     * @throws Doctrine_Connection_Exception   if object is created using the new operator and there are no
     *                                         open connections
     * @throws Doctrine_Record_Exception       if the cleanData operation fails somehow
     */
    public function __construct(?Doctrine_Table $table = null, bool $isNewEntry = false)
    {
        if ($table !== null) {
            $this->_table = $table;
            $exists       = !$isNewEntry;
        } else {
            // @phpstan-ignore-next-line
            $this->_table = Doctrine_Core::getTable(static::class);
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
                $this->_state = State::TDIRTY();
            } else {
                $this->_state = State::TCLEAN();
                $this->resetModified();
            }

            // set the default values for this record
            $this->assignDefaultValues();
        } else {
            $this->_state = State::CLEAN();
            $this->resetModified();

            if ($this->isInProxyState()) {
                $this->_state = State::PROXY();
            }
        }

        // Doctrine_Table does not have the repository yet during dummy record creation.
        /** @var Doctrine_Table_Repository|null */
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
     *
     * @param  boolean $bool
     * @return boolean $bool
     */
    public function serializeReferences($bool = null)
    {
        if ($bool !== null) {
            $this->_serializeReferences = $bool;
        }
        return $this->_serializeReferences;
    }

    /**
     * this method is used for setting up relations and attributes
     * it should be implemented by child classes
     *
     * @return void
     */
    public function setUp(): void
    {
    }

    public function setTableDefinition(): void
    {
    }

    /**
     * construct
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the constructor procedure
     *
     * @return void
     */
    public function construct()
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
     * @param  string         $when  'post' or 'pre'
     * @param  string         $type  serialize, unserialize, save, delete, update, insert, validate, dqlSelect, dqlDelete, hydrate
     * @param  Doctrine_Event $event event raised
     * @return Doctrine_Event        the event generated using the type, if not specified
     */
    public function invokeSaveHooks($when, $type, $event = null)
    {
        $func = $when . ucfirst($type);

        if ($event === null) {
            $constant = constant('Doctrine_Event::RECORD_' . strtoupper($type));
            $event = new Doctrine_Event($this, $constant);
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
     *
     * @return void
     */
    public function clearInvokedSaveHooks()
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
    public function isValid($deep = false, $hooks = true)
    {
        if (!$this->_table->getAttribute(Doctrine_Core::ATTR_VALIDATE)) {
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

        // Run validation process
        $event = new Doctrine_Event($this, Doctrine_Event::RECORD_VALIDATE);
        $this->preValidate($event);
        $this->getTable()->getRecordListener()->preValidate($event);

        $validator = new Doctrine_Validator();
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
            $stateBeforeLock = $this->_state;
            $this->_state = $this->_state->lock();

            foreach ($this->_references as $reference) {
                if ($reference instanceof Doctrine_Record) {
                    if (!$valid = $reference->isValid($deep)) {
                        break;
                    }
                } elseif ($reference instanceof Doctrine_Collection) {
                    foreach ($reference as $record) {
                        if (!$valid = $record->isValid($deep)) {
                            break;
                        }
                    }
                }
            }
            $this->_state = $stateBeforeLock;
        }

        return $valid;
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure, doing any custom / specialized
     * validations that are neccessary.
     *
     * @return void
     */
    protected function validate()
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure only when the record is going to be
     * updated.
     *
     * @return void
     */
    protected function validateOnUpdate()
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure only when the record is going to be
     * inserted into the data store the first time.
     *
     * @return void
     */
    protected function validateOnInsert()
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the serializing procedure.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function preSerialize($event)
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the serializing procedure.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function postSerialize($event)
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the serializing procedure.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function preUnserialize($event)
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the serializing procedure.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function postUnserialize($event)
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function preSave($event)
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function postSave($event)
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the deletion procedure.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function preDelete($event)
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the deletion procedure.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function postDelete($event)
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure only when the record is going to be
     * updated.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function preUpdate($event)
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure only when the record is going to be
     * updated.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function postUpdate($event)
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure only when the record is going to be
     * inserted into the data store the first time.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function preInsert($event)
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the saving procedure only when the record is going to be
     * inserted into the data store the first time.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function postInsert($event)
    {
    }

    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure. Useful for cleaning up data before
     * validating it.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function preValidate($event)
    {
    }
    /**
     * Empty template method to provide concrete Record classes with the possibility
     * to hook into the validation procedure.
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function postValidate($event)
    {
    }

    /**
     * Empty template method to provide Record classes with the ability to alter DQL select
     * queries at runtime
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function preDqlSelect($event)
    {
    }

    /**
     * Empty template method to provide Record classes with the ability to alter DQL update
     * queries at runtime
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function preDqlUpdate($event)
    {
    }

    /**
     * Empty template method to provide Record classes with the ability to alter DQL delete
     * queries at runtime
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function preDqlDelete($event)
    {
    }

    /**
     * Empty template method to provide Record classes with the ability to alter hydration
     * before it runs
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function preHydrate($event)
    {
    }

    /**
     * Empty template method to provide Record classes with the ability to alter hydration
     * after it runs
     *
     * @param  Doctrine_Event $event
     * @return void
     */
    public function postHydrate($event)
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

        $message .= '  ' . count($errorStack) . ' field' . (count($errorStack) > 1 ?  's' : null) . ' had validation error' . (count($errorStack) > 1 ?  's' : null) . ":\n\n";
        foreach ($errorStack as $field => $errors) {
            $message .= '    * ' . count($errors) . ' validator' . (count($errors) > 1 ?  's' : null) . " failed on $field (" . implode(', ', $errors) . ")\n";
        }
        return $message;
    }

    /**
     * retrieves the ErrorStack. To be called after a failed validation attempt (@see isValid()).
     *
     * @return Doctrine_Validator_ErrorStack    returns the errorStack associated with this record
     */
    public function getErrorStack()
    {
        if ($this->_errorStack === null) {
            $this->_errorStack = new Doctrine_Validator_ErrorStack(static::class);
        }
        return $this->_errorStack;
    }

    /**
     * assigns the ErrorStack or returns it if called without parameters
     *
     * @param  Doctrine_Validator_ErrorStack|null $stack errorStack to be assigned for this record
     * @return Doctrine_Validator_ErrorStack returns the errorStack associated with this record
     */
    public function errorStack(?Doctrine_Validator_ErrorStack $stack = null): Doctrine_Validator_ErrorStack
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
     *
     * @return void
     */
    public function assignInheritanceValues()
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
     * setDefaultValues
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

            if ($value instanceof Doctrine_Null || $overwrite) {
                $this->_data[$fieldName] = $default;
                $this->_modified[] = $fieldName;
                $this->_state = State::TDIRTY();
            }
        }

        return true;
    }

    /**
     * leaves the $data array only with values whose key is a field inside this
     * record and returns the values that were removed from $data. Also converts
     * any values of 'null' to objects of type Doctrine_Null.
     *
     * @param  array $data data array to be cleaned
     * @return array values cleaned from data
     */
    public function cleanData(array &$data, bool $deserialize = true): array
    {
        $tmp  = $data;
        $data = [];

        $deserializers = $deserialize ? $this->getDeserializers() : [];

        foreach ($this->_table->getFieldNames() as $fieldName) {
            if (isset($tmp[$fieldName])) { // value present
                if (!empty($deserializers)) {
                    $tmp[$fieldName] = $this->_table->deserializeColumnValue($tmp[$fieldName], $fieldName, $deserializers);
                }
                $data[$fieldName] = $tmp[$fieldName];
            } elseif (array_key_exists($fieldName, $tmp)) { // null
                $data[$fieldName] = null;
            } elseif (!isset($this->_data[$fieldName])) { // column not in data
                $data[$fieldName] = Doctrine_Null::instance();
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
            $this->_state = State::PROXY();
        }
    }

    /**
     * prepareIdentifiers
     * prepares identifiers for later use
     *
     * @param  boolean $exists whether or not this record exists in persistent data store
     * @return void
     */
    private function prepareIdentifiers($exists = true)
    {
        switch ($this->_table->getIdentifierType()) {
            case Doctrine_Core::IDENTIFIER_AUTOINC:
            case Doctrine_Core::IDENTIFIER_SEQUENCE:
            case Doctrine_Core::IDENTIFIER_NATURAL:
                $name = $this->_table->getIdentifier();
                if (is_array($name)) {
                    $name = $name[0];
                }
                if ($exists) {
                    if (isset($this->_data[$name]) && !$this->_data[$name] instanceof Doctrine_Null) {
                        $this->_id[$name] = $this->_data[$name];
                    }
                }
                break;
            case Doctrine_Core::IDENTIFIER_COMPOSITE:
                $names = (array) $this->_table->getIdentifier();

                foreach ($names as $name) {
                    if ($this->_data[$name] instanceof Doctrine_Null) {
                        $this->_id[$name] = null;
                    } else {
                        $this->_id[$name] = $this->_data[$name];
                    }
                }
                break;
        }
    }

    /**
     * serialize
     * this method is automatically called when an instance of Doctrine_Record is serialized
     *
     * @return string
     */
    public function serialize()
    {
        $event = new Doctrine_Event($this, Doctrine_Event::RECORD_SERIALIZE);

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
        $vars['_state'] = (string) $vars['_state'];

        $data = $this->_data;
        if ($this->exists()) {
            $data = array_merge($data, $this->_id);
        }

        foreach ($data as $k => $v) {
            if ($v instanceof Doctrine_Null) {
                unset($vars['_data'][$k]);
                continue;
            }

            $type = $this->_table->getTypeOf($k);

            if ($v instanceof Doctrine_Record && $type != 'object') {
                unset($vars['_data'][$k]);
                continue;
            }

            switch ($type) {
                case 'array':
                case 'object':
                    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
                        $vars['_data'][$k] = serialize($vars['_data'][$k]);
                    }
                    break;
                case 'gzip':
                    $vars['_data'][$k] = gzcompress($vars['_data'][$k]);
                    break;
                case 'enum':
                    $vars['_data'][$k] = $this->_table->enumIndex($k, $vars['_data'][$k]);
                    break;
            }
        }

        $str = serialize($vars);

        $this->postSerialize($event);
        $this->getTable()->getRecordListener()->postSerialize($event);

        return $str;
    }

    /**
     * this method is automatically called everytime an instance is unserialized
     *
     * @param  string $serialized Doctrine_Record as serialized string
     * @throws Doctrine_Record_Exception        if the cleanData operation fails somehow
     * @return void
     */
    public function unserialize($serialized)
    {
        $event = new Doctrine_Event($this, Doctrine_Event::RECORD_UNSERIALIZE);

        $manager = Doctrine_Manager::getInstance();
        $connection = $manager->getConnectionForComponent(static::class);

        // @phpstan-ignore-next-line
        $this->_table = $connection->getTable(static::class);

        $this->preUnserialize($event);
        $this->getTable()->getRecordListener()->preUnserialize($event);

        $array = unserialize($serialized);

        foreach ($array as $k => $v) {
            if ($k === '_state') {
                $this->_state = State::from((int) $v);
            } else {
                $this->$k = $v;
            }
        }

        foreach ($this->_data as $k => $v) {
            switch ($this->_table->getTypeOf($k)) {
                case 'array':
                case 'object':
                    if (version_compare(PHP_VERSION, '5.4.0', '<')) {
                        $this->_data[$k] = unserialize($this->_data[$k]);
                    }
                    break;
                case 'gzip':
                    $this->_data[$k] = gzuncompress($this->_data[$k]);
                    break;
                case 'enum':
                    $this->_data[$k] = $this->_table->enumValue($k, $this->_data[$k]);
                    break;
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
     * refresh
     * refresh internal data from the database
     *
     * @param bool $deep If true, fetch also current relations. Caution: this deletes
     *                   any aggregated values you may have queried beforee
     *
     * @throws Doctrine_Record_Exception        When the refresh operation fails (when the database row
     *                                          this record represents does not exist anymore)
     *
     * @return $this|null
     */
    public function refresh(bool $deep = false): ?self
    {
        $id = $this->identifier();
        if (!is_array($id)) {
            $id = [$id];
        }
        if (empty($id)) {
            return null;
        }
        $id = array_values($id);

        $overwrite = $this->getTable()->getAttribute(Doctrine_Core::ATTR_HYDRATE_OVERWRITE);
        $this->getTable()->setAttribute(Doctrine_Core::ATTR_HYDRATE_OVERWRITE, true);

        if ($deep) {
            $query = $this->getTable()->createQuery();
            foreach (array_keys($this->_references) as $name) {
                $query->leftJoin(static::class . ".$name");
            }
            $query->where(implode(' = ? AND ', (array)$this->getTable()->getIdentifier()) . ' = ?');
            $this->clearRelated();
            $record = $query->fetchOne($id);
        } else {
            // Use HYDRATE_ARRAY to avoid clearing object relations
            /** @phpstan-var array<string, mixed>|null */
            $record = $this->getTable()->find($id, hydrate_array: true);
            if ($record) {
                $this->hydrate($record);
            }
        }

        $this->getTable()->setAttribute(Doctrine_Core::ATTR_HYDRATE_OVERWRITE, $overwrite);

        if ($record === null) {
            throw new Doctrine_Record_Exception('Failed to refresh. Record does not exist.');
        }

        $this->resetModified();

        $this->prepareIdentifiers();

        $this->_state = State::CLEAN();

        return $this;
    }

    /**
     * refresh
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
                if ($reference instanceof Doctrine_Collection) {
                    $this->_references[$alias] = $reference;
                } elseif ($reference instanceof Doctrine_Record) {
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
            if ($reference instanceof Doctrine_Collection) {
                $this->_references[$name] = $reference;
            } elseif ($reference instanceof Doctrine_Record) {
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
     * @return void
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
        if ($this->hasReference($name) && !$this->_references[$name] instanceof Doctrine_Null) {
            return true;
        }

        $reference = $this->$name;
        if ($reference instanceof Doctrine_Record) {
            $exists = $reference->exists();
        } elseif ($reference instanceof Doctrine_Collection) {
            throw new Doctrine_Record_Exception(
                'You can only call relatedExists() on a relationship that ' .
                'returns an instance of Doctrine_Record'
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
     * @return Doctrine_Table        a Doctrine_Table object
     * @phpstan-return T
     */
    public function getTable(): Doctrine_Table
    {
        return $this->_table;
    }

    /**
     * return all the internal data (columns)
     *
     * @return array                        an array containing all the properties
     * @phpstan-return array<string, mixed>
     */
    public function getData()
    {
        return $this->_data;
    }

    /**
     * returns the value of a property (column). If the property is not yet loaded
     * this method does NOT load it.
     *
     * @param  string $fieldName name of the property
     * @throws Doctrine_Record_Exception    if trying to get an unknown property
     * @return mixed
     */
    public function rawGet($fieldName)
    {
        if (!array_key_exists($fieldName, $this->_data)) {
            throw new Doctrine_Record_Exception('Unknown property ' . $fieldName);
        }
        if ($this->_data[$fieldName] instanceof Doctrine_Null) {
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
    public function load(array $data = [])
    {
        // only load the data from database if the Doctrine_Record is in proxy state
        if ($this->exists() && $this->isInProxyState()) {
            $id = $this->identifier();

            if (!is_array($id)) {
                $id = [$id];
            }

            if (empty($id)) {
                return false;
            }

            $table = $this->getTable();
            $data  = empty($data) ? $table->find($id, hydrate_array: true) : $data;

            if (is_array($data)) {
                foreach ($data as $field => $value) {
                    if (is_string($field) && $table->hasField($field) && (!array_key_exists($field, $this->_data) || $this->_data[$field] instanceof Doctrine_Null)) {
                        $this->_data[$field] = $value;
                    }
                }
            }

            if ($this->isModified()) {
                $this->_state = State::DIRTY();
            } elseif (!$this->isInProxyState()) {
                $this->_state = State::CLEAN();
            }

            return true;
        }

        return false;
    }

    /**
     * indicates whether record has any not loaded fields
     *
     * @return boolean
     */
    public function isInProxyState()
    {
        $count = 0;
        foreach ($this->_data as $value) {
            if (!$value instanceof Doctrine_Null) {
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
     *
     * @param  string $fieldName
     * @param  string $accessor
     * @return boolean|null
     */
    public function hasAccessor($fieldName, $accessor = null)
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
     *
     * @param  string $fieldName
     * @return void
     */
    public function clearAccessor($fieldName)
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
    public function getAccessor($fieldName)
    {
        if ($this->hasAccessor($fieldName)) {
            $componentName = $this->_table->getComponentName();
            return self::$_customAccessors[$componentName][$fieldName];
        }

        return null;
    }

    /**
     * gets all accessors for this component instance
     *
     * @return array $accessors
     */
    public function getAccessors()
    {
        $componentName = $this->_table->getComponentName();
        return isset(self::$_customAccessors[$componentName]) ? self::$_customAccessors[$componentName] : [];
    }

    /**
     * sets a fieldname to have a custom mutator or check if a field has a custom
     * mutator defined (when called without the $mutator parameter)
     *
     * @param  string $fieldName
     * @param  string $mutator
     * @return boolean|null
     */
    public function hasMutator($fieldName, $mutator = null)
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
     *
     * @param  string $fieldName
     * @return string|null
     */
    public function getMutator($fieldName)
    {
        if ($this->hasMutator($fieldName)) {
            $componentName = $this->_table->getComponentName();
            return self::$_customMutators[$componentName][$fieldName];
        }

        return null;
    }

    /**
     * clears the custom mutator for a field name
     *
     * @param  string $fieldName
     * @return void
     */
    public function clearMutator($fieldName)
    {
        $componentName = $this->_table->getComponentName();
        unset(self::$_customMutators[$componentName][$fieldName]);
    }

    /**
     * gets all custom mutators for this component instance
     *
     * @return array $mutators
     */
    public function getMutators()
    {
        $componentName = $this->_table->getComponentName();
        return self::$_customMutators[$componentName];
    }

    /**
     * Set a fieldname to have a custom accessor and mutator
     *
     * @param string $fieldName
     * @param string $accessor
     * @param string $mutator
     *
     * @return void
     */
    public function hasAccessorMutator($fieldName, $accessor, $mutator)
    {
        $this->hasAccessor($fieldName, $accessor);
        $this->hasMutator($fieldName, $mutator);
    }

    /**
     * returns a value of a property or a related component
     *
     * @param  string  $fieldName name of the property or related component
     * @param  boolean $load      whether or not to invoke the loading procedure
     * @throws Doctrine_Record_Exception        if trying to get a value of unknown property / related component
     * @return mixed
     */
    public function get($fieldName, $load = true, bool $accessors = false)
    {
        static $inAccessor = [];

        if (empty($inAccessor[$fieldName]) && $accessors
        && ($this->_table->getAttribute(Doctrine_Core::ATTR_AUTO_ACCESSOR_OVERRIDE) || $this->hasAccessor($fieldName))) {
            $componentName = $this->_table->getComponentName();
            $accessor = $this->getAccessor($fieldName) ?? 'get' . Doctrine_Inflector::classify($fieldName);

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
        $value = Doctrine_Null::instance();

        if (array_key_exists($fieldName, $this->_values)) {
            return $this->_values[$fieldName];
        }

        if (array_key_exists($fieldName, $this->_data)) {
            if ($this->_data[$fieldName] instanceof Doctrine_Null && $load) {
                $this->load();
            }

            if ($this->_data[$fieldName] instanceof Doctrine_Null) {
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

            if ($this->_references[$fieldName] instanceof Doctrine_Null) {
                return null;
            }

            return $this->_references[$fieldName];
        } catch (Doctrine_Table_Exception $e) {
            $success = false;
            foreach ($this->_table->getFilters() as $filter) {
                try {
                    $value = $filter->filterGet($this, $fieldName);
                    $success = true;
                } catch (Doctrine_Exception $e) {
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
     * @param mixed   $fieldName name of the property or reference
     * @param mixed   $value     value of the property or reference
     * @param boolean $load      whether or not to refresh / load the uninitialized record data
     *
     * @throws Doctrine_Record_Exception    if trying to set a value for unknown property / related component
     * @throws Doctrine_Record_Exception    if trying to set a value of wrong type for related component
     *
     * @return $this
     */
    public function set($fieldName, $value, $load = true, bool $mutators = false)
    {
        static $inMutator = [];

        if (empty($inMutator[$fieldName]) && $mutators
        && ($this->_table->getAttribute(Doctrine_Core::ATTR_AUTO_ACCESSOR_OVERRIDE) || $this->hasMutator($fieldName))) {
            $componentName = $this->_table->getComponentName();
            $mutator       = $this->getMutator($fieldName) ?? 'set' . Doctrine_Inflector::classify($fieldName);

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

        // old _set
        if (array_key_exists($fieldName, $this->_values)) {
            $this->_values[$fieldName] = $value;
        } elseif (array_key_exists($fieldName, $this->_data)) {
            $column = $this->_table->getColumnDefinition($this->_table->getColumnName($fieldName));
            assert($column !== null);

            if ($value instanceof Doctrine_Record && $column['type'] !== 'object') {
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
                    case State::CLEAN():
                    case State::PROXY():
                        $this->_state = State::DIRTY();
                        break;
                    case State::TCLEAN():
                        $this->_state = State::TDIRTY();
                        break;
                }
            }
        } else {
            try {
                $this->coreSetRelated($fieldName, $value);
            } catch (Doctrine_Table_Exception $e) {
                $success = false;
                foreach ($this->_table->getFilters() as $filter) {
                    try {
                        $value = $filter->filterSet($this, $fieldName, $value);
                        $success = true;
                    } catch (Doctrine_Exception $e) {
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
     *
     * @param  string $name
     * @return void
     */
    public function __unset($name)
    {
        if (array_key_exists($name, $this->_data)) {
            $this->_data[$name] = [];
        } elseif (isset($this->_references[$name])) {
            if ($this->_references[$name] instanceof Doctrine_Record) {
                $this->_pendingDeletes[]  = $this->$name;
                $this->_references[$name] = Doctrine_Null::instance();
            } elseif ($this->_references[$name] instanceof Doctrine_Collection) {
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
     * @return void
     */
    public function mapValue($name, $value = null)
    {
        $this->_values[$name] = $value;
    }

    /**
     * Tests whether a mapped value exists
     *
     * @param  string $name the name of the property
     * @return boolean
     */
    public function hasMappedValue($name)
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
    protected function isValueModified(array $column, mixed $old, mixed $new): bool
    {
        if ($new instanceof Doctrine_Expression) {
            return true;
        }

        if ($new instanceof Doctrine_Null || $new === null) {
            return !($old instanceof Doctrine_Null) && $old !== null;
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

        $type = $column['type'];

        if (is_numeric($old) && is_numeric($new)) {
            if (in_array($type, ['decimal', 'float'])) {
                return $old * 100 != $new * 100;
            }

            if (in_array($type, ['integer', 'int'])) {
                return $old != $new;
            }
        }

        if (in_array($type, ['timestamp', 'date', 'datetime'])) {
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
     * @param  string                                   $name  related component alias in the relation
     * @param  Doctrine_Record|Doctrine_Collection|null $value object to be linked as a related component
     * @return $this|null
     *
     * @todo Refactor. What about composite keys?
     */
    public function coreSetRelated($name, $value)
    {
        $rel = $this->_table->getRelation($name);

        if ($value === null) {
            $value = Doctrine_Null::instance();
        }

        // one-to-many or one-to-one relation
        if ($rel instanceof Doctrine_Relation_ForeignKey || $rel instanceof Doctrine_Relation_LocalKey) {
            if (!$rel->isOneToOne()) {
                // one-to-many relation found
                if (!($value instanceof Doctrine_Collection)) {
                    throw new Doctrine_Record_Exception("Couldn't call Doctrine_Core::set(), second argument should be an instance of Doctrine_Collection when setting one-to-many references.");
                }

                if (isset($this->_references[$name])) {
                    $this->_references[$name]->setData($value->getData());

                    return $this;
                }
            } else {
                $localFieldName   = $this->_table->getFieldName($rel->getLocal());
                $foreignFieldName = '';

                if (!$value instanceof Doctrine_Null) {
                    $relatedTable     = $rel->getTable();
                    $foreignFieldName = $relatedTable->getFieldName($rel->getForeign());
                }

                // one-to-one relation found
                if (!($value instanceof Doctrine_Record) && !($value instanceof Doctrine_Null)) {
                    throw new Doctrine_Record_Exception("Couldn't call Doctrine_Core::set(), second argument should be an instance of Doctrine_Record or Doctrine_Null when setting one-to-one references.");
                }

                if ($rel instanceof Doctrine_Relation_LocalKey) {
                    if (!$value instanceof Doctrine_Null && !empty($foreignFieldName) && $foreignFieldName != $value->getTable()->getIdentifier()) {
                        $this->set($localFieldName, $value->rawGet($foreignFieldName), false, mutators: true);
                    } else {
                        // FIX: Ticket #1280 fits in this situation
                        $this->set($localFieldName, $value, false, mutators: true);
                    }
                } elseif (!$value instanceof Doctrine_Null) {
                    // We should only be able to reach $foreignFieldName if we have a Doctrine_Record on hands
                    $value->set($foreignFieldName, $this, false);
                }
            }
        } elseif ($rel instanceof Doctrine_Relation_Association) {
            // join table relation found
            if (!($value instanceof Doctrine_Collection)) {
                throw new Doctrine_Record_Exception("Couldn't call Doctrine_Core::set(), second argument should be an instance of Doctrine_Collection when setting many-to-many references.");
            }
        }

        $this->_references[$name] = $value;

        return null;
    }

    /**
     * test whether a field (column, mapped value, related component, accessor) is accessible by @see get()
     *
     * @param  string $fieldName
     * @return boolean
     */
    public function contains($fieldName)
    {
        return array_key_exists($fieldName, $this->_data)
            || isset($this->_id[$fieldName])
            || isset($this->_values[$fieldName])
            || (isset($this->_references[$fieldName]) && !$this->_references[$fieldName] instanceof Doctrine_Null);
    }

    /**
     * returns Doctrine_Record instances which need to be deleted on save
     *
     * @return array
     */
    public function getPendingDeletes()
    {
        return $this->_pendingDeletes;
    }

    /**
     * returns Doctrine_Record instances which need to be unlinked (deleting the relation) on save
     *
     * @return array $pendingUnlinks
     */
    public function getPendingUnlinks()
    {
        return $this->_pendingUnlinks;
    }

    /**
     * resets pending record unlinks
     *
     * @return void
     */
    public function resetPendingUnlinks()
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
     * @param  Doctrine_Connection $conn optional connection parameter
     * @throws Exception                    if record is not valid and validation is active
     * @return void
     */
    public function save(Doctrine_Connection $conn = null)
    {
        if ($conn === null) {
            $conn = $this->_table->getConnection();
        }
        $conn->unitOfWork->saveGraph($this);
    }

    /**
     * tries to save the object and all its related components.
     * In contrast to Doctrine_Record::save(), this method does not
     * throw an exception when validation fails but returns TRUE on
     * success or FALSE on failure.
     *
     * @param  Doctrine_Connection $conn optional connection parameter
     * @return bool TRUE if the record was saved sucessfully without errors, FALSE otherwise.
     */
    public function trySave(Doctrine_Connection $conn = null)
    {
        try {
            $this->save($conn);
            return true;
        } catch (Doctrine_Validator_Exception $ignored) {
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
     * @param  Doctrine_Connection $conn optional connection parameter
     * @throws Doctrine_Connection_Exception        if some of the key values was null
     * @throws Doctrine_Connection_Exception        if there were no key fields
     * @throws Doctrine_Connection_Exception        if something fails at database level
     * @return bool
     */
    public function replace(Doctrine_Connection $conn = null)
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
     * @return         array $a
     * @phpstan-return array<string, mixed>
     */
    public function getModified(bool $old = false, bool $last = false): array
    {
        $serializers = $this->getSerializers();

        $a = [];
        $modified = $last ? $this->_lastModified : $this->_modified;
        foreach ($modified as $fieldName) {
            $column = $this->_table->getColumnDefinition($this->_table->getColumnName($fieldName));
            assert($column !== null);

            if ($old) {
                $oldValue = isset($this->_oldValues[$fieldName])
                    ? $this->_oldValues[$fieldName]
                    : $this->_table->getDefaultValueOf($fieldName);
                $curValue = $this->get($fieldName, false);

                if ($this->isValueModified($column, $oldValue, $curValue)) {
                    $a[$fieldName] = $oldValue;
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
     * @return array
     */
    public function getLastModified($old = false)
    {
        return $this->getModified($old, true);
    }

    /** @return Deserializer\DeserializerInterface[] */
    public function getDeserializers(): array
    {
        return array_merge(
            Doctrine_Manager::getInstance()->getDeserializers(),
            $this->_table->getDeserializers(),
        );
    }

    /** @return Serializer\SerializerInterface[] */
    public function getSerializers(): array
    {
        return array_merge(
            Doctrine_Manager::getInstance()->getSerializers(),
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
     * @return array
     * @todo   What about a little bit more expressive name? getPreparedData?
     */
    public function getPrepared()
    {
        $prepared = [];
        $modifiedFields = $this->_modified;

        $serializers = $this->getSerializers();

        foreach ($modifiedFields as $field) {
            $dataValue = $this->_data[$field];

            if ($dataValue instanceof Doctrine_Null) {
                $prepared[$field] = null;
                continue;
            }

            $column = $this->_table->getColumnDefinition($this->_table->getColumnName($field));
            assert($column !== null);

            // we cannot use serializers in this case but it should be fine
            if ($dataValue instanceof Doctrine_Record && $column['type'] !== 'object') {
                $prepared[$field] = $dataValue->getIncremented();
                if ($prepared[$field] !== null) {
                    $this->_data[$field] = $prepared[$field];
                }
                continue;
            }

            foreach ($serializers as $serializer) {
                try {
                    $prepared[$field] = $serializer->serialize($dataValue, $column, $this->_table);
                    continue 2;
                } catch (Serializer\Exception\Incompatible) {
                }
            }

            $prepared[$field] = $dataValue;
        }

        return $prepared;
    }

    /**
     * implements Countable interface
     *
     * @return integer          the number of columns in this record
     */
    public function count()
    {
        return count($this->_data);
    }

    /**
     * alias for @see count()
     *
     * @return integer          the number of columns in this record
     */
    public function columnCount()
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
    public function toArray($deep = true, $prefixKey = false): ?array
    {
        if ($this->_state->isLocked()) {
            return null;
        }

        $stateBeforeLock = $this->_state;
        $this->_state = $this->_state->lock();

        $a = [];

        foreach ($this as $column => $value) {
            if ($value instanceof Doctrine_Null || is_object($value)) {
                $value = null;
            }

            $columnValue = $this->get($column, false, accessors: true);

            if ($columnValue instanceof Doctrine_Record) {
                $a[$column] = $columnValue->getIncremented();
            } else {
                $a[$column] = $columnValue;
            }
        }

        if ($this->_table->getIdentifierType() == Doctrine_Core::IDENTIFIER_AUTOINC) {
            $i = $this->_table->getIdentifier();
            if (is_array($i)) {
                throw new Doctrine_Exception("Multi column identifiers are not supported for auto increments");
            }
            $a[$i] = $this->getIncremented();
        }

        if ($deep) {
            foreach ($this->_references as $key => $relation) {
                if (!$relation instanceof Doctrine_Null) {
                    $a[$key] = $relation->toArray($deep, $prefixKey);
                }
            }
        }

        // [FIX] Prevent mapped Doctrine_Records from being displayed fully
        foreach ($this->_values as $key => $value) {
            $a[$key] = ($value instanceof Doctrine_Record || $value instanceof Doctrine_Collection)
                ? $value->toArray($deep, $prefixKey) : $value;
        }

        $this->_state = $stateBeforeLock;

        return $a;
    }

    /**
     * merges this record with an array of values
     * or with another existing instance of this object
     *
     * @see    fromArray()
     * @link   http://www.doctrine-project.org/documentation/manual/1_1/en/working-with-models
     * @param  array|Doctrine_Record $data array of data to merge, see link for documentation
     * @param  bool                  $deep whether or not to merge relations
     * @return void
     */
    public function merge($data, $deep = true)
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
     * @return void
     */
    public function fromArray(array $array, $deep = true)
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
                $method = 'set' . Doctrine_Inflector::classify($key);

                try {
                    if (is_callable([$this, $method])) {
                        $this->$method($value);
                    }
                } catch (Doctrine_Record_Exception $e) {
                }
            }
        }

        if ($refresh) {
            $this->refresh();
        }
    }

    /**
     * synchronizes a Doctrine_Record instance and its relations with data from an array
     *
     * it expects an array representation of a Doctrine_Record similar to the return
     * value of the toArray() method. If the array contains relations it will create
     * those that don't exist, update the ones that do, and delete the ones missing
     * on the array but available on the Doctrine_Record (unlike @see fromArray() that
     * does not touch what it is not in $array)
     *
     * @param array $array representation of a Doctrine_Record
     * @param bool  $deep  whether or not to act on relations
     *
     * @return void
     */
    public function synchronizeWithArray(array $array, $deep = true)
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
     *
     * @return boolean
     */
    public function exists()
    {
        return !$this->_state->isTransient();
    }

    /**
     * returns true if this record was modified, otherwise false
     *
     * @param  boolean $deep whether to process also the relations for changes
     * @return boolean
     */
    public function isModified($deep = false)
    {
        $modified = $this->_state->isDirty();
        if (!$modified && $deep) {
            if ($this->_state->isLocked()) {
                return false;
            }

            $stateBeforeLock = $this->_state;
            $this->_state = $this->_state->lock();

            foreach ($this->_references as $reference) {
                if ($reference instanceof Doctrine_Record) {
                    if ($modified = $reference->isModified($deep)) {
                        break;
                    }
                } elseif ($reference instanceof Doctrine_Collection) {
                    foreach ($reference as $record) {
                        if ($modified = $record->isModified($deep)) {
                            break 2;
                        }
                    }
                }
            }
            $this->_state = $stateBeforeLock;
        }
        return $modified;
    }

    /**
     * checks existence of properties and related components
     *
     * @param  mixed $fieldName name of the property or reference
     * @return boolean
     */
    public function hasRelation($fieldName)
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
     *
     * @return boolean      true if successful
     */
    public function delete(Doctrine_Connection $conn = null)
    {
        if ($conn == null) {
            $conn = $this->_table->getConnection();
        }
        return $conn->unitOfWork->delete($this);
    }

    /**
     * generates a copy of this object. Returns an instance of the same class of $this.
     *
     * @param  boolean $deep whether to duplicates the objects targeted by the relations
     * @return static
     */
    public function copy($deep = false)
    {
        $data   = $this->_data;
        $idtype = $this->_table->getIdentifierType();
        if ($idtype === Doctrine_Core::IDENTIFIER_AUTOINC || $idtype === Doctrine_Core::IDENTIFIER_SEQUENCE) {
            $id = $this->_table->getIdentifier();
            if (is_scalar($id)) {
                unset($data[$id]);
            }
        }

        /** @var static */
        $ret = $this->_table->create($data);

        foreach ($data as $key => $val) {
            if (!($val instanceof Doctrine_Null)) {
                $ret->_modified[] = $key;
            }
        }

        if ($deep) {
            foreach ($this->_references as $key => $value) {
                if ($value instanceof Doctrine_Collection) {
                    foreach ($value as $valueKey => $record) {
                        $ret->{$key}[$valueKey] = $record->copy($deep);
                    }
                } elseif ($value instanceof Doctrine_Record) {
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
     * @return void
     */
    public function assignIdentifier($id = false)
    {
        if ($id === false) {
            $this->_id = [];
            $this->_data = $this->cleanData($this->_data, deserialize: false);
            $this->_state = State::TCLEAN();
            $this->resetModified();
        } elseif ($id === true) {
            $this->prepareIdentifiers(true);
            $this->_state = State::CLEAN();
            $this->resetModified();
        } else {
            if (is_array($id)) {
                foreach ($id as $fieldName => $value) {
                    $this->_id[$fieldName]   = $value;
                    $this->_data[$fieldName] = $value;
                }
            } else {
                $name = $this->_table->getIdentifier();
                assert(!is_array($name));
                $this->_id[$name] = $id;
                $this->_data[$name] = $id;
            }
            $this->_state = State::CLEAN();
            $this->resetModified();
        }
    }

    /**
     * returns the primary keys of this object
     *
     * @return array
     */
    public function identifier()
    {
        return $this->_id;
    }

    /**
     * returns the value of autoincremented primary key of this object (if any)
     *
     * @return integer|null
     * @todo   Better name?
     */
    final public function getIncremented()
    {
        $id = current($this->_id);
        if ($id === false) {
            return null;
        }

        return $id;
    }

    /**
     * getLast
     * this method is used internally by Doctrine_Query
     * it is needed to provide compatibility between
     * records and collections
     *
     * @return $this
     */
    public function getLast()
    {
        return $this;
    }

    /**
     * tests whether a relation is set
     *
     * @param  string $name relation alias
     * @return boolean
     */
    public function hasReference($name)
    {
        return isset($this->_references[$name]);
    }

    /**
     * gets a related component
     *
     * @param  string $name
     * @return Doctrine_Record|Doctrine_Collection|null
     */
    public function reference($name)
    {
        if (isset($this->_references[$name])) {
            return $this->_references[$name];
        }

        return null;
    }

    /**
     * gets a related component and fails if it does not exist
     *
     * @param  string $name
     * @return Doctrine_Record|Doctrine_Collection
     * @throws Doctrine_Record_Exception        if trying to get an unknown related component
     */
    public function obtainReference($name)
    {
        if (isset($this->_references[$name])) {
            return $this->_references[$name];
        }
        throw new Doctrine_Record_Exception("Unknown reference $name");
    }

    /**
     * get all related components
     *
     * @return array    various Doctrine_Collection or Doctrine_Record instances
     */
    public function getReferences()
    {
        return $this->_references;
    }

    /**
     * set a related component
     *
     * @param string $alias
     * @param Doctrine_Record|Doctrine_Collection $coll
     *
     * @return void
     */
    final public function setRelated($alias, Doctrine_Record|Doctrine_Collection $coll)
    {
        $this->_references[$alias] = $coll;
    }

    /**
     * loadReference
     * loads a related component
     *
     * @throws Doctrine_Table_Exception             if trying to load an unknown related component
     * @param  string $name alias of the relation
     * @return void
     */
    public function loadReference($name)
    {
        $rel                      = $this->_table->getRelation($name);
        $this->_references[$name] = $rel->fetchRelatedFor($this);
    }

    /**
     * call
     *
     * @param  callable $callback valid callback
     * @param  string   $column   column name
     * @param  mixed    ...$args  optional callback arguments
     * @return $this provides a fluent interface
     */
    public function call($callback, $column, ...$args)
    {
        // Put $column on front of $args to maintain previous behavior
        array_unshift($args, $column);

        if (isset($args[0])) {
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
    public function unshiftFilter(Doctrine_Record_Filter $filter): Doctrine_Table
    {
        return $this->_table->unshiftFilter($filter);
    }

    /**
     * unlink
     * removes links from this record to given records
     * if no ids are given, it removes all links
     *
     * @param  string  $alias related component alias
     * @param  array   $ids   the identifiers of the related records
     * @param  boolean $now   whether or not to execute now or set as pending unlinks
     * @return $this  this object (fluent interface)
     */
    public function unlink($alias, $ids = [], $now = false)
    {
        $ids = (array) $ids;

        // fix for #1622
        if (!isset($this->_references[$alias]) && $this->hasRelation($alias)) {
            $this->loadReference($alias);
        }

        $allIds = [];
        if (isset($this->_references[$alias])) {
            if ($this->_references[$alias] instanceof Doctrine_Record) {
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
     * @return $this  this object (fluent interface)
     */
    public function unlinkInDb($alias, $ids = [])
    {
        $rel = $this->getTable()->getRelation($alias);

        if ($rel instanceof Doctrine_Relation_Association) {
            $q = $rel->getAssociationTable()
                ->createQuery()
                ->delete()
                ->where($rel->getLocal() . ' = ?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $q->whereIn($rel->getForeign(), $ids);
            }

            $q->execute();
        } elseif ($rel instanceof Doctrine_Relation_ForeignKey) {
            $q = $rel->getTable()->createQuery()
                ->update()
                ->set($rel->getForeign(), '?', [null])
                ->addWhere($rel->getForeign() . ' = ?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $identifier = $rel->getTable()->getIdentifier();
                if (is_array($identifier)) {
                    throw new Doctrine_Exception("Cannot unlink related components with multi-column identifiers");
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
     * @param  array   $ids   the identifiers of the related records
     * @param  boolean $now   wether or not to execute now or set pending
     * @return $this  this object (fluent interface)
     */
    public function link($alias, $ids, $now = false)
    {
        $ids = (array) $ids;

        if (!count($ids)) {
            return $this;
        }

        if (!$this->exists() || $now === false) {
            $relTable = $this->getTable()->getRelation($alias)->getTable();

            $identifier = $relTable->getIdentifier();
            if (is_array($identifier)) {
                throw new Doctrine_Exception("Cannot link related components with multi-column identifiers");
            }

            /** @phpstan-var Doctrine_Collection<static> */
            $records = $relTable->createQuery()
                ->whereIn($identifier, $ids)
                ->execute();

            foreach ($records as $record) {
                if ($this->$alias instanceof Doctrine_Record) {
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
     * @return $this  this object (fluent interface)
     */
    public function linkInDb($alias, $ids)
    {
        $identifier = array_values($this->identifier());
        $identifier = array_shift($identifier);

        $rel = $this->getTable()->getRelation($alias);

        if ($rel instanceof Doctrine_Relation_Association) {
            $modelClassName = $rel->getAssociationTable()->getComponentName();
            $localFieldName = $rel->getLocalFieldName();
            $localFieldDef  = $rel->getAssociationTable()->getColumnDefinition($localFieldName);
            assert($localFieldDef !== null);

            if ($localFieldDef['type'] == 'integer') {
                $identifier = (integer) $identifier;
            }

            $foreignFieldName = $rel->getForeignFieldName();
            $foreignFieldDef  = $rel->getAssociationTable()->getColumnDefinition($foreignFieldName);
            assert($foreignFieldDef !== null);

            if ($foreignFieldDef['type'] == 'integer') {
                foreach ($ids as $i => $id) {
                    $ids[$i] = (integer) $id;
                }
            }

            foreach ($ids as $id) {
                /** @var Doctrine_Record $record */
                $record                    = new $modelClassName;
                $record[$localFieldName]   = $identifier;
                $record[$foreignFieldName] = $id;
                $record->save();
            }
        } elseif ($rel instanceof Doctrine_Relation_ForeignKey) {
            $q = $rel->getTable()
                ->createQuery()
                ->update()
                ->set($rel->getForeign(), '?', array_values($this->identifier()));

            if (count($ids) > 0) {
                $identifier = $rel->getTable()->getIdentifier();
                if (is_array($identifier)) {
                    throw new Doctrine_Exception("Cannot link related components with multi-column identifiers");
                }
                $q->whereIn($identifier, $ids);
            }

            $q->execute();
        } elseif ($rel instanceof Doctrine_Relation_LocalKey) {
            $q = $this->getTable()
                ->createQuery()
                ->update()
                ->set($rel->getLocalFieldName(), '?', $ids);

            if (count($ids) > 0) {
                $identifier = $rel->getTable()->getIdentifier();
                if (is_array($identifier)) {
                    throw new Doctrine_Exception("Cannot link related components with multi-column identifiers");
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
     *
     * @return void
     */
    protected function resetModified()
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
     *
     * @return void
     */
    public function free($deep = false)
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
                    if (!($reference instanceof Doctrine_Null)) {
                        $reference->free($deep);
                    }
                }
            }

            $this->_references = [];
        }
    }

    /**
     * magic method
     *
     * @return string representation of this object
     */
    public function __toString()
    {
        return (string) $this->_oid;
    }

    /**
     * addListener
     *
     * @phpstan-param Doctrine_Record_Listener_Interface|Doctrine_Overloadable<Doctrine_Record_Listener_Interface> $listener
     * @return $this
     */
    public function addListener(Doctrine_Record_Listener_Interface|Doctrine_Overloadable $listener, ?string $name = null): self
    {
        $this->_table->addRecordListener($listener, $name);
        return $this;
    }

    /**
     * getListener
     *
     * @phpstan-return Doctrine_Record_Listener_Interface|Doctrine_Overloadable<Doctrine_Record_Listener_Interface>|null
     */
    public function getListener(): Doctrine_Record_Listener_Interface|Doctrine_Overloadable|null
    {
        return $this->_table->getRecordListener();
    }

    /**
     * setListener
     *
     * @phpstan-param Doctrine_Record_Listener_Interface|Doctrine_Overloadable<Doctrine_Record_Listener_Interface> $listener
     * @return $this
     */
    public function setListener(Doctrine_Record_Listener_Interface|Doctrine_Overloadable $listener): self
    {
        $this->_table->setRecordListener($listener);
        return $this;
    }

    /**
     * defines or retrieves an index
     * if the second parameter is set this method defines an index
     * if not this method retrieves index named $name
     *
     * @param  string $name       the name of the index
     * @param  array  $definition the definition array
     * @return mixed
     */
    public function index(string $name, array $definition = [])
    {
        if (!$definition) {
            return $this->_table->getIndex($name);
        }

        $this->_table->addIndex($name, $definition);
    }

    /**
     * Defines a n-uple of fields that must be unique for every record.
     *
     * This method Will automatically add UNIQUE index definition
     * and validate the values on save. The UNIQUE index is not created in the
     * database until you use @see export().
     *
     * @param  array $fields            values are fieldnames
     * @param  array $options           array of options for unique validator
     * @param  bool  $createUniqueIndex Whether or not to create a unique index in the database
     * @return void
     */
    public function unique($fields, $options = [], $createUniqueIndex = true)
    {
        $this->_table->unique($fields, $options, $createUniqueIndex);
    }

    /**
     * @param  string|int $attr
     * @param  mixed      $value
     * @return void
     */
    public function setAttribute($attr, $value)
    {
        $this->_table->setAttribute($attr, $value);
    }

    public function setTableName(string $tableName): void
    {
        $this->_table->setTableName($tableName);
    }

    public function setInheritanceMap(array $map): void
    {
        $this->_table->inheritanceMap = $map;
    }

    public function setSubclasses(array $map): void
    {
        $class = get_class($this);
        // Set the inheritance map for subclasses
        if (isset($map[$class])) {
            // fix for #1621
            $mapFieldNames  = $map[$class];
            $mapColumnNames = [];

            foreach ($mapFieldNames as $fieldName => $val) {
                $mapColumnNames[$this->getTable()->getColumnName($fieldName)] = $val;
            }

            $this->_table->inheritanceMap = $mapColumnNames;
            return;
        } else {
            // Put an index on the key column
            $mapFieldName = array_keys(end($map));
            $this->index($this->getTable()->getTableName() . '_' . $mapFieldName[0], ['fields' => [$mapFieldName[0]]]);
        }

        // Set the subclasses array for the parent class
        $this->_table->subclasses = array_keys($map);
    }

    /**
     * attribute
     * sets or retrieves an option
     *
     * @see    Doctrine_Core::ATTR_* constants   availible attributes
     * @param  mixed $attr
     * @param  mixed $value
     * @return mixed
     */
    public function attribute($attr, $value)
    {
        if ($value == null) {
            if (is_array($attr)) {
                foreach ($attr as $k => $v) {
                    $this->_table->setAttribute($k, $v);
                }
            } else {
                return $this->_table->getAttribute($attr);
            }
        } else {
            $this->_table->setAttribute($attr, $value);
        }
    }

    /**
     * Binds One-to-One aggregate relation
     *
     * @param  string|array ...$args First: the name of the related component
     *                               Second: relation options
     * @see    Doctrine_Relation::_$definition
     * @return $this          this object
     */
    public function hasOne(...$args)
    {
        $this->_table->bind($args, Doctrine_Relation::ONE);

        return $this;
    }

    /**
     * Binds One-to-Many / Many-to-Many aggregate relation
     *
     * @param  string|array ...$args First: the name of the related component
     *                               Second: relation options
     * @see    Doctrine_Relation::_$definition
     * @return $this          this object
     */
    public function hasMany(...$args)
    {
        $this->_table->bind($args, Doctrine_Relation::MANY);

        return $this;
    }

    /**
     * Sets a column definition
     *
     * @param  string  $name
     * @param  string  $type
     * @param  integer $length
     * @param  mixed   $options
     * @return void
     */
    public function hasColumn($name, $type = null, $length = null, $options = [])
    {
        $this->_table->setColumn($name, $type, $length, $options);
    }

    /**
     * Set multiple column definitions at once
     *
     * @param  array $definitions
     * @return void
     */
    public function hasColumns(array $definitions)
    {
        foreach ($definitions as $name => $options) {
            $length = isset($options['length']) ? $options['length']:null;
            $this->hasColumn($name, $options['type'], $length, $options);
        }
    }

    /**
     * Customize the array of options for a column or multiple columns. First
     * argument can be a single field/column name or an array of them. The second
     * argument is an array of options.
     *
     *     [php]
     *     public function setTableDefinition(): void
     *     {
     *         parent::setTableDefinition();
     *         $this->setColumnOptions('username', array(
     *             'unique' => true
     *         ));
     *     }
     *
     * @param  string $name
     * @param  array  $options
     * @return void
     */
    public function setColumnOptions($name, array $options)
    {
        $this->_table->setColumnOptions($name, $options);
    }

    /**
     * Set an individual column option
     *
     * @param  string $columnName
     * @param  string $option
     * @param  mixed  $value
     * @return void
     */
    public function setColumnOption($columnName, $option, $value)
    {
        $this->_table->setColumnOption($columnName, $option, $value);
    }

    /**
     * bindQueryParts
     * binds query parts to given component
     *
     * @param  array $queryParts an array of pre-bound query parts
     * @return $this          this object
     */
    public function bindQueryParts(array $queryParts)
    {
        $this->_table->bindQueryParts($queryParts);

        return $this;
    }

    /**
     * Adds a check constraint.
     *
     * This method will add a CHECK constraint to the record table.
     *
     * @param  mixed  $constraint either a SQL constraint portion or an array of CHECK constraints. If array, all values will be added as constraint
     * @param  string $name       optional constraint name. Not used if $constraint is an array.
     * @return $this      this object
     */
    public function check($constraint, $name = null)
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
