<?php

/**
 * @phpstan-template T of Doctrine_Table
 */
abstract class Doctrine_Record_Abstract extends Doctrine_Access
{
    /**
     * reference to associated Doctrine_Table instance
     * @phpstan-var T
     */
    protected Doctrine_Table $_table;

    public function setTableDefinition(): void
    {
    }

    public function setUp(): void
    {
    }

    /**
     * returns the associated table object
     *
     * @return Doctrine_Table the associated table object
     * @phpstan-return T
     */
    public function getTable(): Doctrine_Table
    {
        return $this->_table;
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
     * index
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
}
