<?php

use Doctrine1\Serializer\WithSerializers;
use Doctrine1\Deserializer\WithDeserializers;
use Doctrine1\Deserializer;
use Laminas\Validator\AbstractValidator;

/**
 * @phpstan-template T of Doctrine_Record
 */
class Doctrine_Table extends Doctrine_Configurable implements Countable
{
    use WithSerializers;
    use WithDeserializers;

    /**
     * temporary data which is then loaded into Doctrine_Record::$data
     */
    protected array $data = [];

    /**
     * @var string[] $identifier   The field names of all fields that are part of the identifier/primary key
     */
    protected array $identifier = [];

    /**
     * @see Doctrine_Identifier constants
     * the type of identifier this table uses
     */
    protected ?int $identifierType = null;

    /**
     * Doctrine_Connection object that created this table
     */
    protected Doctrine_Connection $connection;

    /**
     * first level cache
     */
    protected array $identityMap = [];

    /**
     * record repository
     */
    protected ?Doctrine_Table_Repository $repository = null;

    /**
     * an array of column definitions,
     * keys are column names and values are column definitions
     *
     * @var array<string, array<string,mixed>> $columns
     * @phpstan-var array<string, array{
     *   type: string,
     *   length: int,
     *   notnull?: bool,
     *   values?: array,
     *   default?: mixed,
     *   autoincrement?: bool,
     *   values?: mixed[],
     * }>
     */
    protected array $columns = [];

    /**
     * Array of unique sets of fields. These values are validated on save
     *
     * @var mixed[] $uniques
     */
    protected array $uniques = [];

    /**
     * @var string[] $fieldNames an array of field names, used to look up field names
     *                            from column names. Keys are column
     *                            names and values are field names.
     *                            Alias for columns are here.
     */
    protected array $fieldNames = [];

    /**
     *
     * @var string[] $columnNames an array of column names
     *                             keys are field names and values column names.
     *                             used to look up column names from field names.
     *                             this is the reverse lookup map of $fieldNames.
     */
    protected array $columnNames = [];

    /**
     * cached column count, Doctrine_Record uses this column count in when
     * determining its state
     */
    protected int $columnCount = 0;

    /**
     * whether or not this table has default values
     */
    protected bool $hasDefaultValues = false;

    /**
     * name of the component, for example component name of the GroupTable is 'Group'
     * @phpstan-var class-string<T>
     */
    public string $name;

    /** database table name, in most cases this is the same as component name but in some cases where one-table-multi-class inheritance is used this will be the name of the inherited table */
    public string $tableName = '';

    /** inheritanceMap is used for inheritance mapping, keys representing columns and values
     * the column values that should correspond to child classes */
    public array $inheritanceMap = [];

    /** enum value arrays */
    public array $enumMap = [];

    /** table type (mysql example: INNODB) */
    public ?string $type = null;
    public ?string $charset = null;
    public ?string $collate = null;
    public mixed $treeImpl = null;
    public array $treeOptions = [];
    public array $indexes = [];
    public array $foreignKeys = [];

    /** the check constraints of this table, eg. 'price > dicounted_price' */
    public array $checks = [];

    /** the parent classes of this component */
    public array $parents = [];
    public array $queryParts = [];
    public array $subclasses = [];
    public mixed $orderBy = null;
    public ?\ReflectionClass $declaringClass = null;

    /** Some databases need sequences instead of auto incrementation primary keys */
    public ?string $sequenceName = null;

    protected Doctrine_Relation_Parser $parser;

    /**
     * @see Doctrine_Record_Filter
     * an array containing all record filters attached to this table
     */
    protected array $filters = [];

    /**
     * empty instance of the given model
     * @phpstan-var T
     */
    protected ?Doctrine_Record $record = null;

    /**
     * the constructor
     *
     * @throws        Doctrine_Connection_Exception    if there are no opened connections
     * @param         string              $name           the name of the component
     * @phpstan-param class-string<T> $name
     * @param         Doctrine_Connection $conn           the connection associated with this table
     * @param         boolean             $initDefinition whether to init the in-memory schema
     */
    public function __construct(string $name, Doctrine_Connection $conn, $initDefinition = false)
    {
        $this->connection = $conn;
        $this->name = $name;

        $this->setParent($this->connection);
        $this->connection->addTable($this);

        $this->parser = new Doctrine_Relation_Parser($this);

        if ($charset = $this->getAttribute(Doctrine_Core::ATTR_DEFAULT_TABLE_CHARSET)) {
            $this->charset = $charset;
        }
        if ($collate = $this->getAttribute(Doctrine_Core::ATTR_DEFAULT_TABLE_COLLATE)) {
            $this->collate = $collate;
        }

        if ($initDefinition) {
            $this->record = $record = $this->initDefinition();
            $this->initIdentifier();
            $record->setUp();
        } elseif (empty($this->tableName)) {
            $this->setTableName(Doctrine_Inflector::tableize($this->name));
        }

        $this->filters[]  = new Doctrine_Record_Filter_Standard();
        $this->repository = new Doctrine_Table_Repository($this);

        $this->construct();
    }

    /**
     * Construct template method.
     *
     * This method provides concrete Table classes with the possibility
     * to hook into the constructor procedure. It is called after the
     * Doctrine_Table construction process is finished.
     *
     * @return void
     */
    public function construct()
    {
    }

    /**
     * Initializes the in-memory table definition.
     *
     * @return Doctrine_Record
     * @phpstan-return T
     */
    public function initDefinition(): Doctrine_Record
    {
        $name = $this->name;
        if (!class_exists($name) || empty($name)) {
            throw new Doctrine_Exception("Couldn't find class $name");
        }
        $record = new $name($this);

        $names = [];

        $class = $name;

        // get parent classes

        do {
            if ($class === Doctrine_Record::class) {
                break;
            }

            $name    = $class;
            $names[] = $name;
        } while ($class = get_parent_class($class));

        if ($class === false) {
            throw new Doctrine_Table_Exception('Class "' . $name . '" must be a child class of Doctrine_Record');
        }

        // reverse names
        $names = array_reverse($names);
        // save parents
        array_pop($names);
        $this->parents = $names;

        // create database table
        if (method_exists($record, 'setTableDefinition')) {
            $record->setTableDefinition();
            // get the declaring class of setTableDefinition method
            $method = new ReflectionMethod($this->name, 'setTableDefinition');
            $class = $method->getDeclaringClass();
        } else {
            $class = new ReflectionClass($class);
        }

        foreach (array_reverse($this->parents) as $parent) {
            if ($parent === $class->getName()) {
                continue;
            }
            $ref = new ReflectionClass($parent);

            if ($ref->isAbstract() || !$class->isSubClassOf($parent)) {
                continue;
            }
            $parentTable = $this->connection->getTable($parent);

            $found         = false;
            $parentColumns = $parentTable->getColumns();

            foreach ($parentColumns as $columnName => $definition) {
                if (!isset($definition['primary']) || $definition['primary'] === false) {
                    if (isset($this->columns[$columnName])) {
                        $found = true;
                        break;
                    } elseif (!isset($parentColumns[$columnName]['owner'])) {
                        $parentColumns[$columnName]['owner'] = $parentTable->getComponentName();
                    }
                } else {
                    unset($parentColumns[$columnName]);
                }
            }

            if ($found) {
                continue;
            }

            foreach ($parentColumns as $columnName => $definition) {
                $fullName = $columnName . ' as ' . $parentTable->getFieldName($columnName);
                $this->setColumn($fullName, $definition['type'], $definition['length'], $definition, true);
            }

            break;
        }

        $this->declaringClass = $class;

        $this->columnCount = count($this->columns);

        if (empty($this->tableName)) {
            $this->setTableName(Doctrine_Inflector::tableize($class->getName()));
        }

        return $record;
    }

    /**
     * Initializes the primary key.
     *
     * Called in the construction process, builds the identifier definition
     * copying in the schema the list of the fields which constitutes
     * the primary key.
     *
     * @return int the identifier type
     */
    public function initIdentifier(): int
    {
        $id_count = count($this->identifier);

        if ($id_count > 1) {
            $this->identifierType = Doctrine_Core::IDENTIFIER_COMPOSITE;
            return $this->identifierType;
        }

        if ($id_count == 1) {
            foreach ($this->identifier as $pk) {
                $e = $this->getDefinitionOf($pk);

                if (!$e) {
                    continue;
                }

                $found = false;
                foreach ($e as $option => $value) {
                    if ($found) {
                        break;
                    }

                    $e2 = explode(':', $option);

                    switch (strtolower($e2[0])) {
                        case 'autoincrement':
                        case 'autoinc':
                            if ($value !== false) {
                                $this->identifierType = Doctrine_Core::IDENTIFIER_AUTOINC;
                                $found = true;
                            }
                            break;
                        case 'seq':
                        case 'sequence':
                            $this->identifierType = Doctrine_Core::IDENTIFIER_SEQUENCE;
                            $found                 = true;

                            if (is_string($value)) {
                                $this->sequenceName = $value;
                            } else {
                                if (($sequence = $this->getAttribute(Doctrine_Core::ATTR_DEFAULT_SEQUENCE)) !== null) {
                                    $this->sequenceName = $sequence;
                                } else {
                                    $this->sequenceName = $this->connection->formatter->getSequenceName($this->tableName);
                                }
                            }
                            break;
                    }
                }

                if (!isset($this->identifierType)) {
                    $this->identifierType = Doctrine_Core::IDENTIFIER_NATURAL;
                }
            }

            if (isset($pk)) {
                if (!isset($this->identifierType)) {
                    $this->identifierType = Doctrine_Core::IDENTIFIER_NATURAL;
                }
                $this->identifier = [$pk];
                return $this->identifierType;
            }
        }

        $identifierOptions = $this->getAttribute(Doctrine_Core::ATTR_DEFAULT_IDENTIFIER_OPTIONS);
        $name              = (isset($identifierOptions['name']) && $identifierOptions['name']) ? $identifierOptions['name']:'id';
        $name              = sprintf($name, $this->getTableName());

        $definition = ['type'          => (isset($identifierOptions['type']) && $identifierOptions['type']) ? $identifierOptions['type']:'integer',
                        'length'        => (isset($identifierOptions['length']) && $identifierOptions['length']) ? $identifierOptions['length']:8,
                        'autoincrement' => isset($identifierOptions['autoincrement']) ? $identifierOptions['autoincrement']:true,
                        'primary'       => isset($identifierOptions['primary']) ? $identifierOptions['primary']:true];

        unset($identifierOptions['name'], $identifierOptions['type'], $identifierOptions['length']);
        foreach ($identifierOptions as $key => $value) {
            if (!isset($definition[$key]) || !$definition[$key]) {
                $definition[$key] = $value;
            }
        }

        $this->setColumn($name, $definition['type'], $definition['length'], $definition, true);
        $this->identifier = [$name];
        $this->identifierType = Doctrine_Core::IDENTIFIER_AUTOINC;

        $this->columnCount++;
        return $this->identifierType;
    }

    /**
     * Gets the owner of a column.
     *
     * The owner of a column is the name of the component in a hierarchy that
     * defines the column.
     *
     * @param  string $columnName the column name
     * @return string              the name of the owning/defining component
     */
    public function getColumnOwner($columnName)
    {
        if (isset($this->columns[$columnName]['owner'])) {
            return $this->columns[$columnName]['owner'];
        } else {
            return $this->getComponentName();
        }
    }

    /**
     * Gets the record instance for this table.
     *
     * The Doctrine_Table instance always holds at least one
     * instance of a model so that it can be reused for several things,
     * but primarily it is first used to instantiate all the internal
     * in memory schema definition.
     *
     * @return Doctrine_Record  Empty instance of the record
     * @phpstan-return T
     */
    public function getRecordInstance(): Doctrine_Record
    {
        if ($this->record === null) {
            $this->record = new $this->name;
        }
        return $this->record;
    }

    /**
     * Checks whether a column is inherited from a component further up in the hierarchy.
     *
     * @param  string $columnName The column name
     * @return boolean     TRUE if column is inherited, FALSE otherwise.
     */
    public function isInheritedColumn($columnName)
    {
        return (isset($this->columns[$columnName]['owner']));
    }

    /**
     * Checks whether a field is in the primary key.
     *
     * Checks if $fieldName is part of the table identifier, which defines
     * the one-column or multi-column primary key.
     *
     * @param  string $fieldName The field name
     * @return boolean           TRUE if the field is part of the table identifier/primary key field(s),
     */
    public function isIdentifier(string $fieldName): bool
    {
        return in_array($fieldName, $this->identifier);
    }

    /**
     * Checks whether a field identifier is of type autoincrement.
     *
     * This method checks if the primary key is a AUTOINCREMENT column or
     * if the table uses a natural key.
     *
     * @return boolean TRUE  if the identifier is autoincrement
     *                 FALSE otherwise
     */
    public function isIdentifierAutoincrement()
    {
        return $this->getIdentifierType() === Doctrine_Core::IDENTIFIER_AUTOINC;
    }

    /**
     * Checks whether a field identifier is a composite key.
     *
     * @return boolean TRUE  if the identifier is a composite key,
     *                 FALSE otherwise
     */
    public function isIdentifierComposite()
    {
        return $this->getIdentifierType() === Doctrine_Core::IDENTIFIER_COMPOSITE;
    }

    /**
     * Exports this table to database based on the schema definition.
     *
     * This method create a physical table in the database, using the
     * definition that comes from the component Doctrine_Record instance.
     *
     * @throws Doctrine_Connection_Exception    if some error other than Doctrine_Core::ERR_ALREADY_EXISTS
     *                                          occurred during the create table operation
     * @return void
     */
    public function export()
    {
        $this->connection->export->exportTable($this);
    }

    /**
     * Returns an exportable representation of this object.
     *
     * This method produces a array representation of the table schema, where
     * keys are tableName, columns (@see $columns) and options.
     * The options subarray contains 'primary' and 'foreignKeys'.
     *
     * @param  boolean $parseForeignKeys whether to include foreign keys definition in the options
     * @return array<string, mixed>
     * @phpstan-return array{
     *   tableName: string,
     *   columns: array<string, array<string, mixed>>,
     *   options: array{
     *     primary: string[],
     *     foreignKeys: mixed[],
     *   },
     * }
     */
    public function getExportableFormat($parseForeignKeys = true)
    {
        $columns = [];
        $primary = [];

        foreach ($this->getColumns() as $name => $definition) {
            if (isset($definition['owner'])) {
                continue;
            }

            if ($definition['type'] === 'boolean' && isset($definition['default'])) {
                $definition['default'] = $this->getConnection()->convertBooleans($definition['default']);
            }
            $columns[$name] = $definition;

            if (isset($definition['primary']) && $definition['primary']) {
                $primary[] = $name;
            }
        }

        $options = $this->getOptions();
        $options['foreignKeys'] ??= [];

        if ($parseForeignKeys && $this->getAttribute(Doctrine_Core::ATTR_EXPORT) & Doctrine_Core::EXPORT_CONSTRAINTS) {
            $constraints = [];

            $emptyIntegrity = [
                'onUpdate' => null,
                'onDelete' => null,
            ];

            foreach ($this->getRelations() as $name => $relation) {
                $fk = $relation->toArray();
                $fk['foreignTable'] = $relation->getTable()->getTableName();

                // do not touch tables that have EXPORT_NONE attribute
                if ($relation->getTable()->getAttribute(Doctrine_Core::ATTR_EXPORT) === Doctrine_Core::EXPORT_NONE) {
                    continue;
                }

                if ($relation->getTable() === $this && in_array($relation->getLocal(), $primary)) {
                    if ($relation->hasConstraint()) {
                        throw new Doctrine_Table_Exception('Badly constructed integrity constraints. Cannot define constraint of different fields in the same table.');
                    }
                    continue;
                }

                $integrity = ['onUpdate' => $fk['onUpdate'],
                                   'onDelete' => $fk['onDelete']];

                $fkName = $relation->getForeignKeyName();

                if ($relation instanceof Doctrine_Relation_LocalKey) {
                    $def = ['name'         => $fkName,
                                 'local'        => $relation->getLocalColumnName(),
                                 'foreign'      => $relation->getForeignColumnName(),
                                 'foreignTable' => $relation->getTable()->getTableName()];

                    if ($integrity !== $emptyIntegrity) {
                        $def = array_merge($def, $integrity);
                    }
                    if (($key = $this->checkForeignKeyExists($def, $options['foreignKeys'])) === null) {
                        $options['foreignKeys'][$fkName] = $def;
                    } else {
                        unset($def['name']);
                        $options['foreignKeys'][$key] = array_merge($options['foreignKeys'][$key], $def);
                    }
                }
            }
        }

        $options['primary'] = $primary;

        return [
            'tableName' => $this->tableName,
            'columns' => $columns,
            'options' => $options,
        ];
    }

    /**
     * Check if a foreign definition already exists in the fks array for a
     * foreign table, local and foreign key
     *
     * @param  array<string,mixed>   $def         Foreign key definition to check for
     * @param  array<string,mixed>[] $foreignKeys Array of existing foreign key definitions to check in
     * @return int|string|null $result     Whether or not the foreign key was found
     */
    protected function checkForeignKeyExists($def, $foreignKeys): int|string|null
    {
        foreach ($foreignKeys as $key => $foreignKey) {
            if ($def['local'] == $foreignKey['local'] && $def['foreign'] == $foreignKey['foreign'] && $def['foreignTable'] == $foreignKey['foreignTable']) {
                return $key;
            }
        }
        return null;
    }

    /**
     * Retrieves the relation parser associated with this table.
     *
     * @return Doctrine_Relation_Parser     relation parser object
     */
    public function getRelationParser()
    {
        return $this->parser;
    }

    /**
     * Retrieves all options of this table and the associated values.
     *
     * @return array{
     *   name: string,
     *   tableName: string,
     *   sequenceName: ?string,
     *   inheritanceMap: array,
     *   enumMap: array,
     *   type: ?string,
     *   charset: ?string,
     *   collate: ?string,
     *   treeImpl: mixed,
     *   treeOptions: array,
     *   indexes: array,
     *   parents: array,
     *   queryParts: array,
     *   subclasses: array,
     *   orderBy: mixed,
     *   checks: array,
     * }
     */
    public function getOptions()
    {
        return [
            'name' => $this->name,
            'tableName' => $this->tableName,
            'sequenceName' => $this->sequenceName,
            'inheritanceMap' => $this->inheritanceMap,
            'enumMap' => $this->enumMap,
            'type' => $this->type,
            'charset' => $this->charset,
            'collate' => $this->collate,
            'treeImpl' => $this->treeImpl,
            'treeOptions' => $this->treeOptions,
            'indexes' => $this->indexes,
            'parents' => $this->parents,
            'queryParts' => $this->queryParts,
            'subclasses' => $this->subclasses,
            'orderBy' => $this->orderBy,
            'checks' => $this->checks,
        ];
    }

    /**
     * Adds a foreignKey to the table in-memory definition.
     *
     * This method adds a foreign key to the schema definition.
     * It does not add the key to the physical table in the db; @see export().
     *
     * @param  mixed[] $definition definition of the foreign key
     * @return void
     */
    public function addForeignKey(array $definition)
    {
        $this->foreignKeys[] = $definition;
    }

    /**
     * Adds a check constraint to the table in-memory definition.
     *
     * This method adds a CHECK constraint to the schema definition.
     * It does not add the constraint to the physical table in the
     * db; @see export().
     *
     * @param  mixed $definition
     * @param  string|int|null $name If string used as name for the constraint.
     *                               Otherwise it is indexed numerically.
     * @return $this
     */
    public function addCheckConstraint($definition, $name)
    {
        if (is_string($name)) {
            $this->checks[$name] = $definition;
        } else {
            $this->checks[] = $definition;
        }

        return $this;
    }

    /**
     * Adds an index to this table in-memory definition.
     *
     * This method adds an INDEX to the schema definition.
     * It does not add the index to the physical table in the db; @see export().
     *
     * @param  string $index      index name
     * @param  array  $definition keys are type, fields
     * @return void
     */
    public function addIndex($index, array $definition)
    {
        if (isset($definition['fields'])) {
            foreach ((array) $definition['fields'] as $key => $field) {
                if (is_numeric($key)) {
                    $definition['fields'][$key] = $this->getColumnName($field);
                } else {
                    $columnName = $this->getColumnName($key);

                    unset($definition['fields'][$key]);

                    $definition['fields'][$columnName] = $field;
                }
            }
        }

        $this->indexes[$index] = $definition;
    }

    /**
     * Retrieves an index definition.
     *
     * This method returns a given index definition: @see addIndex().
     *
     * @param  string $index index name; @see addIndex()
     * @return mixed[]|null        array on success, FALSE on failure
     */
    public function getIndex(string $index): ?array
    {
        return $this->indexes[$index] ?? null;
    }

    /**
     * Defines a n-uple of fields that must be unique for every record.
     *
     * This method Will automatically add UNIQUE index definition
     * and validate the values on save. The UNIQUE index is not created in the
     * database until you use @see export().
     *
     * @param  string[] $fields             values are fieldnames
     * @param  mixed[]  $options            array of options for unique validator
     * @param  bool     $createdUniqueIndex Whether or not to create a unique index in the database
     * @return void
     */
    public function unique($fields, $options = [], $createdUniqueIndex = true)
    {
        if ($createdUniqueIndex) {
            $name       = implode('_', $fields) . '_unqidx';
            $definition = ['type' => 'unique', 'fields' => $fields];
            $this->addIndex($name, $definition);
        }

        $this->uniques[] = [$fields, $options];
    }

    /**
     * Adds a relation to the table.
     *
     * This method defines a relation on this table, that will be present on
     * every record belonging to this component.
     *
     * @param  mixed[] $args first value is a string, name of related component;
     *                       second value is array, options for the relation.
     * @see    Doctrine_Relation::_$definition
     * @param  integer $type Doctrine_Relation::ONE or Doctrine_Relation::MANY
     * @return $this
     * @todo   Name proposal: addRelation
     */
    public function bind($args, $type)
    {
        $options = $args[1] ?? [];
        $options['type'] = $type;

        $this->parser->bind($args[0], $options);

        return $this;
    }

    /**
     * Binds One-to-One aggregate relation
     *
     * @param  mixed ...$args first value is a string, name of related component;
     *                        second value is array, options for the relation.
     * @see    Doctrine_Relation::_$definition
     * @return void
     */
    public function hasOne(...$args)
    {
        $this->bind($args, Doctrine_Relation::ONE);
    }

    /**
     * Binds One-to-Many / Many-to-Many aggregate relation
     *
     * @param  mixed ...$args first value is a string, name of related component;
     *                        second value is array, options for the relation.
     * @see    Doctrine_Relation::_$definition
     * @return void
     */
    public function hasMany(...$args)
    {
        $this->bind($args, Doctrine_Relation::MANY);
    }

    /**
     * Tests if a relation exists.
     *
     * This method queries the table definition to find out if a relation
     * is defined for this component. Alias defined with foreignAlias are not
     * recognized as there's only one Doctrine_Relation object on the owning
     * side.
     *
     * @param  string $alias the relation alias to search for.
     * @return boolean           true if the relation exists. Otherwise false.
     */
    public function hasRelation($alias)
    {
        return $this->parser->hasRelation($alias);
    }

    /**
     * Retrieves a relation object for this component.
     *
     * @param  string $alias     relation alias; @see hasRelation()
     * @param  bool   $recursive
     * @return Doctrine_Relation
     */
    public function getRelation(string $alias, bool $recursive = true): Doctrine_Relation
    {
        return $this->parser->getRelation($alias, $recursive);
    }

    /**
     * Retrieves all relation objects defined on this table.
     *
     * @return array
     */
    public function getRelations()
    {
        return $this->parser->getRelations();
    }

    /**
     * Creates a query on this table.
     *
     * This method returns a new Doctrine_Query object and adds the component
     * name of this table as the query 'from' part.
     * <code>
     * $table = Doctrine_Core::getTable('User');
     * $table->createQuery('myuser')
     *       ->where('myuser.Phonenumber = ?', '5551234');
     * </code>
     *
     * @param string $alias name for component aliasing
     *
     * @return Doctrine_Query
     *
     * @psalm-return Doctrine_Query<Doctrine_Record>
     * @phpstan-return Doctrine_Query<T, Doctrine_Query_Type_Select>
     */
    public function createQuery($alias = ''): Doctrine_Query
    {
        if (!empty($alias)) {
            $alias = ' ' . trim($alias);
        }

        $class = $this->getAttribute(Doctrine_Core::ATTR_QUERY_CLASS);

        /** @phpstan-var Doctrine_Query<T, Doctrine_Query_Type_Select> */
        return Doctrine_Query::create(null, $class)
            ->from($this->getComponentName() . $alias);
    }

    /**
     * Gets the internal record repository.
     *
     * @return Doctrine_Table_Repository|null
     */
    public function getRepository()
    {
        return $this->repository;
    }


    /**
     * Get the table orderby statement
     *
     * @param  string  $alias       The alias to use
     * @param  boolean $columnNames Whether or not to use column names instead of field names
     * @return string|null $orderByStatement
     */
    public function getOrderByStatement($alias = null, $columnNames = false)
    {
        if (isset($this->orderBy)) {
            return $this->processOrderBy($alias, $this->orderBy);
        }

        return null;
    }

    /**
     * Process an order by statement to be prefixed with the passed alias and
     * field names converted to column names if the 3rd argument is true.
     *
     * @param  string|null     $alias       The alias to prefix columns with
     * @param  string|string[] $orderBy     The order by to process
     * @param  bool            $columnNames Whether or not to convert field names to column names
     * @return string $orderBy
     */
    public function processOrderBy($alias, $orderBy, $columnNames = false)
    {
        if (!$alias) {
            $alias = $this->getComponentName();
        }

        if (!is_array($orderBy)) {
            $e1 = explode(',', $orderBy);
        } else {
            $e1 = $orderBy;
        }
        $e1 = array_map('trim', $e1);
        foreach ($e1 as $k => $v) {
            $e2 = explode(' ', $v);
            if ($columnNames) {
                $e2[0] = $this->getColumnName($e2[0]);
            }
            if ($this->hasField($this->getFieldName($e2[0]))) {
                $e1[$k] = $alias . '.' . $e2[0];
            } else {
                $e1[$k] = $e2[0];
            }
            if (isset($e2[1])) {
                $e1[$k] .= ' ' . $e2[1];
            }
        }

        return implode(', ', $e1);
    }

    /**
     * Returns a column name for a column alias.
     *
     * If the actual name for the alias cannot be found
     * this method returns the given alias.
     *
     * @param  string|string[] $fieldName column alias
     * @return string column name
     */
    public function getColumnName($fieldName)
    {
        // FIX ME: This is being used in places where an array is passed, but it should not be an array
        // For example in places where Doctrine should support composite foreign/primary keys
        $fieldName = is_array($fieldName) ? $fieldName[0]:$fieldName;

        if (isset($this->columnNames[$fieldName])) {
            return $this->columnNames[$fieldName];
        }

        return strtolower($fieldName);
    }

    /**
     * Retrieves a column definition from this table schema.
     *
     * @param string $columnName
     * @return array column definition; @see $columns
     * @phpstan-return array{
     *   type: string,
     *   length: int,
     *   notnull?: bool,
     *   values?: array,
     *   default?: mixed,
     *   autoincrement?: bool,
     *   values?: mixed[],
     * }|null
     */
    public function getColumnDefinition($columnName): ?array
    {
        if (!isset($this->columns[$columnName])) {
            return null;
        }
        return $this->columns[$columnName];
    }

    /**
     * Returns a column alias for a column name.
     *
     * If no alias can be found the column name is returned.
     *
     * @param  string $columnName column name
     * @return string column alias
     */
    public function getFieldName($columnName)
    {
        if (isset($this->fieldNames[$columnName])) {
            return $this->fieldNames[$columnName];
        }
        return $columnName;
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
     * @param  string|string[] $columnName
     * @param  mixed[] $options
     * @return void
     */
    public function setColumnOptions($columnName, array $options)
    {
        if (is_array($columnName)) {
            foreach ($columnName as $name) {
                $this->setColumnOptions($name, $options);
            }
        } else {
            foreach ($options as $option => $value) {
                $this->setColumnOption($columnName, $option, $value);
            }
        }
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
        if ($option == 'primary') {
            if ($value && !in_array($columnName, $this->identifier)) {
                $this->identifier[] = $columnName;
            } elseif (!$value && in_array($columnName, $this->identifier)) {
                $key = array_search($columnName, $this->identifier);
                unset($this->identifier[$key]);
            }
        }

        $columnName = $this->getColumnName($columnName);
        $this->columns[$columnName][$option] = $value;
    }

    /**
     * Set multiple column definitions at once
     *
     * @param  array<string,mixed> $definitions
     * @return void
     */
    public function setColumns(array $definitions)
    {
        foreach ($definitions as $name => $options) {
            $this->setColumn($name, $options['type'], $options['length'], $options);
        }
    }

    /**
     * Adds a column to the schema.
     *
     * This method does not alter the database table; @see export();
     *
     * @see    $columns;
     * @param  string  $name    column physical name
     * @param  string  $type    type of data
     * @param  integer $length  maximum length
     * @param  mixed   $options
     * @param  boolean $prepend Whether to prepend or append the new column to the column list.
     *                          By default the column gets appended.
     * @throws Doctrine_Table_Exception     if trying use wrongly typed parameter
     * @return void
     */
    public function setColumn($name, $type = null, $length = null, $options = [], $prepend = false)
    {
        if (is_string($options)) {
            $options = explode('|', $options);
        }

        foreach ($options as $k => $option) {
            if (is_numeric($k)) {
                if (!empty($option)) {
                    $options[$option] = true;
                }
                unset($options[$k]);
            }
        }

        // extract column name & field name
        if (stripos($name, ' as ')) {
            if (strpos($name, ' as ')) {
                $parts = explode(' as ', $name);
            } else {
                $parts = explode(' AS ', $name);
            }

            if (count($parts) > 1) {
                $fieldName = $parts[1];
            } else {
                $fieldName = $parts[0];
            }

            $name = strtolower($parts[0]);
        } else {
            $fieldName = $name;
            $name      = strtolower($name);
        }

        $name      = trim($name);
        $fieldName = trim($fieldName);

        if ($prepend) {
            $this->columnNames = array_merge([$fieldName => $name], $this->columnNames);
            $this->fieldNames  = array_merge([$name => $fieldName], $this->fieldNames);
        } else {
            $this->columnNames[$fieldName] = $name;
            $this->fieldNames[$name]       = $fieldName;
        }

        $defaultOptions = $this->getAttribute(Doctrine_Core::ATTR_DEFAULT_COLUMN_OPTIONS);

        if (isset($defaultOptions['length']) && $defaultOptions['length'] && $length == null) {
            $length = $defaultOptions['length'];
        }

        if ($length == null) {
            switch ($type) {
                case 'integer':
                    $length = 8;
                    break;
                case 'decimal':
                    $length = 18;
                    break;
                case 'string':
                case 'clob':
                case 'float':
                case 'array':
                case 'object':
                case 'blob':
                case 'gzip':
                    //All the DataDict driver classes have work-arounds to deal
                    //with unset lengths.
                    $length = null;
                    break;
                case 'boolean':
                    $length = 1;
                    // no break
                case 'date':
                    // YYYY-MM-DD ISO 8601
                    $length = 10;
                    // no break
                case 'time':
                    // HH:NN:SS+00:00 ISO 8601
                    $length = 14;
                    // no break
                case 'timestamp':
                    // YYYY-MM-DDTHH:MM:SS+00:00 ISO 8601
                    $length = 25;
            }
        }

        $options['type']   = $type;
        $options['length'] = $length;

        if (strtolower($fieldName) != $name) {
            $options['alias'] = $fieldName;
        }

        foreach ($defaultOptions as $key => $value) {
            if (!array_key_exists($key, $options) || $options[$key] === null) {
                $options[$key] = $value;
            }
        }

        if ($prepend) {
            $this->columns = array_merge([$name => $options], $this->columns);
        } else {
            $this->columns[$name] = $options;
        }

        if (isset($options['primary']) && $options['primary']) {
            if (!in_array($fieldName, $this->identifier)) {
                $this->identifier[] = $fieldName;
            }
        }
        if (isset($options['default'])) {
            $this->hasDefaultValues = true;
        }
    }

    /**
     * Finds out whether this table has default values for columns.
     *
     * @return boolean
     */
    public function hasDefaultValues()
    {
        return $this->hasDefaultValues;
    }

    /**
     * Retrieves the default value (if any) for a given column.
     *
     * @param  string $fieldName column name
     * @return mixed                default value as set in definition
     */
    public function getDefaultValueOf($fieldName)
    {
        $columnName = $this->getColumnName($fieldName);
        if (!isset($this->columns[$columnName])) {
            throw new Doctrine_Table_Exception("Couldn't get default value. Column $columnName doesn't exist.");
        }

        if (!isset($this->columns[$columnName]['default'])) {
            return null;
        }

        $default = $this->columns[$columnName]['default'];
        if ($default instanceof Closure) {
            $default = $default();
        }

        return $this->deserializeColumnValue($default, $fieldName);
    }

    /** @param Deserializer\DeserializerInterface[]|null $deserializers */
    public function deserializeColumnValue(mixed $value, string $fieldName, ?array $deserializers = null): mixed
    {
        $column = $this->getColumnDefinition($this->getColumnName($fieldName));
        if ($column === null || ($value === null && empty($column['notnull']))) {
            return $value;
        }
        if ($deserializers === null) {
            $deserializers = array_merge(
                Doctrine_Manager::getInstance()->getDeserializers(),
                $this->getDeserializers(),
            );
        }
        foreach ($deserializers as $deserializer) {
            try {
                return $deserializer->deserialize($value, $column, $this);
            } catch (Deserializer\Exception\Incompatible) {
            }
        }
        return $value;
    }

    /**
     * Returns the definition of the identifier key.
     *
     * @return string|string[] can be array if a multi-column primary key is used.
     */
    public function getIdentifier()
    {
        if (empty($this->identifier)) {
            throw new Doctrine_Table_Exception("Table has now identifiers");
        }
        if (count($this->identifier) == 1) {
            return $this->identifier[0];
        }
        return $this->identifier;
    }

    /**
     * Retrieves the type of primary key.
     *
     * This method finds out if the primary key is multifield.
     */
    public function getIdentifierType(): ?int
    {
        return $this->identifierType;
    }

    /**
     * Finds out whether the table definition contains a given column.
     *
     * @param  string $columnName
     * @return boolean
     */
    public function hasColumn($columnName)
    {
        return isset($this->columns[strtolower($columnName)]);
    }

    /**
     * Finds out whether the table definition has a given field.
     *
     * This method returns true if @see hasColumn() returns true or if an alias
     * named $fieldName exists.
     *
     * @param  string $fieldName
     * @return boolean
     */
    public function hasField($fieldName)
    {
        return isset($this->columnNames[$fieldName]);
    }

    /**
     * Sets the default connection for this table.
     *
     * This method assign the connection which this table will use
     * to create queries.
     *
     * @params Doctrine_Connection      a connection object
     * @return $this                    this object; fluent interface
     */
    public function setConnection(Doctrine_Connection $conn)
    {
        $this->connection = $conn;

        $this->setParent($this->connection);

        return $this;
    }

    /**
     * Returns the connection associated with this table (if any).
     *
     * @return Doctrine_Connection     the connection object
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Creates a new record.
     *
     * This method create a new instance of the model defined by this table.
     * The class of this record is the subclass of Doctrine_Record defined by
     * this component. The record is not created in the database until you
     * call @save().
     *
     * @param array $array an array where keys are field names and
     *                              values representing field values. Can
     *                              contain also related components;
     *
     * @see Doctrine_Record::fromArray()
     *
     * @return Doctrine_Record
     *
     * @phpstan-return T
     */
    public function create(array $array = []): Doctrine_Record
    {
        /** @phpstan-var T $record */
        $record = new $this->name($this, true);
        $record->fromArray($array);
        return $record;
    }

    /**
     * Adds a named query in the query registry.
     *
     * This methods register a query object with a name to use in the future.
     *
     * @see           createNamedQuery()
     * @param         string                $queryKey query key name to use for storage
     * @param         string|Doctrine_Query $query    DQL string or object
     * @phpstan-param string|Doctrine_Query<T, Doctrine_Query_Type> $query
     * @return        void
     */
    public function addNamedQuery($queryKey, $query)
    {
        $registry = Doctrine_Manager::getInstance()->getQueryRegistry();
        $registry->add($this->getComponentName() . '/' . $queryKey, $query);
    }

    /**
     * Creates a named query from one in the query registry.
     *
     * This method clones a new query object from a previously registered one.
     *
     * @see            addNamedQuery()
     * @param          string $queryKey query key name
     * @return         Doctrine_Query
     * @phpstan-return Doctrine_Query<T, Doctrine_Query_Type>
     */
    public function createNamedQuery($queryKey)
    {
        $queryRegistry = Doctrine_Manager::getInstance()->getQueryRegistry();

        if (strpos($queryKey, '/') !== false) {
            $e = explode('/', $queryKey);

            return $queryRegistry->get($e[1], $e[0]);
        }

        return $queryRegistry->get($queryKey, $this->getComponentName());
    }

    /**
     * @phpstan-return T|Doctrine_Collection<T>|array<string,mixed>|null
     */
    public function find(array|int|string $params = [], bool $hydrate_array = false, ?string $name = null): Doctrine_Collection|Doctrine_Record|array|null
    {
        $hydrationMode = $hydrate_array ? Doctrine_Core::HYDRATE_ARRAY : Doctrine_Core::HYDRATE_RECORD;
        $params = array_values((array) $params);

        try {
            // We're dealing with a named query
            if ($name !== null) {
                // Check for possible cross-access
                if (strpos($name, '/') !== false) {
                    list($ns, $name) = explode('/', $name);
                } else {
                    $ns = $this->getComponentName();
                }

                // Define query to be used
                if (!Doctrine_Manager::getInstance()->getQueryRegistry()->has($name, $ns)) {
                    throw new Doctrine_Table_Exception("Could not find query named $name.");
                }

                $q = $this->createNamedQuery($name);
                /** @var Doctrine_Collection<T>|array<string,mixed>[]|T|array<string,mixed>|null */
                return $q->execute($params, $hydrationMode);
            }

            // We're passing a single ID or an array of IDs
            $q = $this->createQuery('dctrn_find')
                ->where('dctrn_find.' . implode(' = ? AND dctrn_find.', (array) $this->getIdentifier()) . ' = ?')
                ->limit(1);

            // Executing query
            /** @var T|array<string,mixed>|null */
            return $q->fetchOne($params, $hydrationMode);
        } finally {
            if (isset($q)) {
                $q->free();
            }
        }
    }

    /**
     * Retrieves all the records stored in this table.
     *
     * @psalm-return Doctrine_Collection<Doctrine_Record>|array
     * @phpstan-return Doctrine_Collection<T>|array<string,mixed>[]
     */
    public function findAll(bool $hydrate_array = false): Doctrine_Collection|array
    {
        $hydrationMode = $hydrate_array ? Doctrine_Core::HYDRATE_ARRAY : Doctrine_Core::HYDRATE_RECORD;
        /** @phpstan-var Doctrine_Collection<T>|array<string,mixed>[] */
        return $this->createQuery('dctrn_find')->execute([], $hydrationMode);
    }

    /**
     * Finds records in this table with a given SQL where clause.
     *
     * @param          string $dql           DQL WHERE clause to use
     * @param          array  $params        query parameters (a la PDO)
     * @phpstan-return Doctrine_Collection<T>|array<string,mixed>[]
     */
    public function findBySql($dql, $params = [], bool $hydrate_array = false): Doctrine_Collection|array
    {
        $hydrationMode = $hydrate_array ? Doctrine_Core::HYDRATE_ARRAY : Doctrine_Core::HYDRATE_RECORD;
        /** @phpstan-var Doctrine_Collection<T>|array<string,mixed>[] */
        return $this->createQuery('dctrn_find')
            ->where($dql)->execute($params, $hydrationMode);
    }

    /**
     * Finds records in this table with a given DQL where clause.
     *
     * @param          string  $dql           DQL WHERE clause
     * @param          mixed[] $params        preparated statement parameters
     * @phpstan-return Doctrine_Collection<T>|array<string,mixed>[]
     */
    public function findByDql($dql, $params = [], bool $hydrate_array = false): Doctrine_Collection|array
    {
        $hydrationMode = $hydrate_array ? Doctrine_Core::HYDRATE_ARRAY : Doctrine_Core::HYDRATE_RECORD;
        $parser = $this->createQuery();
        $query  = 'FROM ' . $this->getComponentName() . ' dctrn_find WHERE ' . $dql;

        /** @phpstan-var Doctrine_Collection<T>|array<string,mixed>[] */
        return $parser->query($query, $params, $hydrationMode);
    }

    /**
     * Find records basing on a field.
     *
     * @param          string $fieldName     field for the WHERE clause
     * @param          string $value         prepared statement parameter
     * @phpstan-return Doctrine_Collection<T>|array<string,mixed>[]
     */
    public function findBy($fieldName, $value, bool $hydrate_array = false): Doctrine_Collection|array
    {
        $hydrationMode = $hydrate_array ? Doctrine_Core::HYDRATE_ARRAY : Doctrine_Core::HYDRATE_RECORD;
        /** @phpstan-var Doctrine_Collection<T>|array<string,mixed>[] */
        return $this->createQuery('dctrn_find')
            ->where($this->buildFindByWhere($fieldName), (array) $value)
            ->execute([], $hydrationMode);
    }

    /**
     * Finds the first record that satisfy the clause.
     *
     * @param          string $fieldName     field for the WHERE clause
     * @param          scalar $value         prepared statement parameter
     * @phpstan-return T|array<string,mixed>|null
     */
    public function findOneBy($fieldName, $value, bool $hydrate_array = false): Doctrine_Record|array|null
    {
        $hydrationMode = $hydrate_array ? Doctrine_Core::HYDRATE_ARRAY : Doctrine_Core::HYDRATE_RECORD;
        /** @phpstan-var T|array<string,mixed>|null */
        return $this->createQuery('dctrn_find')
            ->where($this->buildFindByWhere($fieldName), (array) $value)
            ->limit(1)
            ->fetchOne([], $hydrationMode);
    }

    /**
     * Finds result of a named query.
     *
     * This method fetches data using the provided $queryKey to choose a named
     * query in the query registry.
     *
     * @param          string  $queryKey      the query key
     * @param          mixed[] $params        prepared statement params (if any)
     * @phpstan-return Doctrine_Collection<T>|array<string,mixed>[]
     */
    public function execute($queryKey, $params = [], bool $hydrate_array = false): Doctrine_Collection|array
    {
        $hydrationMode = $hydrate_array ? Doctrine_Core::HYDRATE_ARRAY : Doctrine_Core::HYDRATE_RECORD;
        /** @phpstan-var Doctrine_Collection<T>|array<string,mixed>[] */
        return $this->createNamedQuery($queryKey)->execute($params, $hydrationMode);
    }

    /**
     * Fetches one record with a named query.
     *
     * This method uses the provided $queryKey to clone and execute
     * the associated named query in the query registry.
     *
     * @param          string  $queryKey      the query key
     * @param          mixed[] $params        prepared statement params (if any)
     * @phpstan-return T|array<string,mixed>|null
     */
    public function executeOne($queryKey, $params = [], bool $hydrate_array = false): Doctrine_Record|array|null
    {
        $hydrationMode = $hydrate_array ? Doctrine_Core::HYDRATE_ARRAY : Doctrine_Core::HYDRATE_RECORD;
        /** @phpstan-var T|array<string,mixed>|null */
        return $this->createNamedQuery($queryKey)->fetchOne($params, $hydrationMode);
    }

    /**
     * Clears the first level cache (identityMap).
     *
     * This method ensures that records are reloaded from the db.
     *
     * @return void
     * @todo   what about a more descriptive name? clearIdentityMap?
     */
    public function clear()
    {
        $this->identityMap = [];
    }

    /**
     * Adds a record to the first level cache (identity map).
     *
     * This method is used internally to cache records, ensuring that only one
     * object that represents a sql record exists in all scopes.
     *
     * @param Doctrine_Record $record
     *
     * @phpstan-param T $record
     *
     * @return boolean                      true if record was not present in the map
     *
     * @todo Better name? registerRecord?
     */
    public function addRecord(Doctrine_Record $record): bool
    {
        $id = implode(' ', $record->identifier());

        if (isset($this->identityMap[$id])) {
            return false;
        }

        $this->identityMap[$id] = $record;

        return true;
    }

    /**
     * Removes a record from the identity map.
     *
     * This method deletes from the cache the given record; can be used to
     * force reloading of an object from database.
     *
     * @param Doctrine_Record $record
     *
     * @phpstan-param T $record
     *
     * @return boolean                  true if the record was found and removed,
     *                                  false if the record wasn't found.
     */
    public function removeRecord(Doctrine_Record $record): bool
    {
        $id = implode(' ', $record->identifier());

        if (isset($this->identityMap[$id])) {
            unset($this->identityMap[$id]);
            return true;
        }

        return false;
    }

    /**
     * Returns a new record.
     *
     * This method checks if a internal record exists in identityMap, if does
     * not exist it creates a new one.
     *
     * @return         Doctrine_Record
     * @phpstan-return T
     */
    public function getRecord()
    {
        if (empty($this->data)) {
            $recordName = $this->getComponentName();
            return new $recordName($this, true);
        }

        $identifierFieldNames = $this->getIdentifier();

        if (!is_array($identifierFieldNames)) {
            $identifierFieldNames = [$identifierFieldNames];
        }

        $found = false;
        $id = [];
        foreach ($identifierFieldNames as $fieldName) {
            if (!isset($this->data[$fieldName])) {
                // primary key column not found return new record
                $found = true;
                break;
            }
            $id[] = $this->data[$fieldName];
        }

        if ($found) {
            $recordName = $this->getComponentName();
            $record = new $recordName($this, true);
            $this->data = [];
            return $record;
        }

        $id = implode(' ', $id);

        if (isset($this->identityMap[$id])) {
            $record = $this->identityMap[$id];
            if ($record->getTable()->getAttribute(Doctrine_Core::ATTR_HYDRATE_OVERWRITE) && !$record->state()->isLocked()) {
                $record->hydrate($this->data);
                if ($record->state()->equals(Doctrine_Record_State::PROXY())) {
                    if (!$record->isInProxyState()) {
                        $record->state(Doctrine_Record_State::CLEAN());
                    }
                }
            } else {
                $record->hydrate($this->data, false);
            }
        } else {
            $recordName = $this->getComponentName();
            $record = new $recordName($this);
            $this->identityMap[$id] = $record;
        }
        $this->data = [];

        return $record;
    }

    /**
     * Get the classname to return. Most often this is just the options['name'].
     *
     * Check the subclasses option and the inheritanceMap for each subclass to see
     * if all the maps in a subclass is met. If this is the case return that
     * subclass name. If no subclasses match or if there are no subclasses defined
     * return the name of the class for this tables record.
     *
     * @todo this function could use reflection to check the first time it runs
     * if the subclassing option is not set.
     *
     * @return         string The name of the class to create
     * @phpstan-return class-string<T>
     * @deprecated
     */
    public function getClassnameToReturn()
    {
        if (!isset($this->subclasses)) {
            return $this->name;
        }
        foreach ($this->subclasses as $subclass) {
            $table          = $this->connection->getTable($subclass);
            $inheritanceMap = $table->inheritanceMap;
            $nomatch        = false;
            foreach ($inheritanceMap as $key => $value) {
                if (!isset($this->data[$key]) || $this->data[$key] != $value) {
                    $nomatch = true;
                    break;
                }
            }
            if (!$nomatch) {
                /** @phpstan-var class-string<T> */
                return $table->getComponentName();
            }
        }
        return $this->name;
    }

    /**
     * @param          string|int|null $id database row id
     * @phpstan-return ?T
     */
    final public function getProxy($id = null): ?Doctrine_Record
    {
        if ($id !== null) {
            $identifierColumnNames = $this->getIdentifierColumnNames();
            $query                 = 'SELECT ' . implode(', ', (array) $identifierColumnNames)
                . ' FROM ' . $this->getTableName()
                . ' WHERE ' . implode(' = ? && ', (array) $identifierColumnNames) . ' = ?';
            $query = $this->applyInheritance($query);

            $params = array_merge([$id], array_values($this->inheritanceMap));

            $this->data = $this->connection->execute($query, $params)->fetch(PDO::FETCH_ASSOC);

            if ($this->data === false) {
                return null;
            }
        }
        return $this->getRecord();
    }

    /**
     * applyInheritance
     *
     * @param  string $where query where part to be modified
     * @return string                   query where part with column aggregation inheritance added
     */
    final public function applyInheritance($where)
    {
        if (!empty($this->inheritanceMap)) {
            $a = [];
            foreach ($this->inheritanceMap as $field => $value) {
                $a[] = $this->getColumnName($field) . ' = ?';
            }
            $i = implode(' AND ', $a);
            $where .= ' AND ' . $i;
        }
        return $where;
    }

    /**
     * Implements Countable interface.
     *
     * @return integer number of records in the table
     */
    public function count()
    {
        return $this->createQuery()->count();
    }

    /**
     * @return Doctrine_Query
     *
     * @phpstan-return Doctrine_Query<T, Doctrine_Query_Type_Select>
     *
     * @psalm-return Doctrine_Query<Doctrine_Record>
     */
    public function getQueryObject(): Doctrine_Query
    {
        $graph = $this->createQuery();
        $graph->load($this->getComponentName());
        return $graph;
    }

    /**
     * Retrieves the enum values for a given field.
     *
     * @param  string $fieldName
     * @return mixed[]
     */
    public function getEnumValues($fieldName)
    {
        $columnName = $this->getColumnName($fieldName);
        if (isset($this->columns[$columnName]['values'])) {
            return $this->columns[$columnName]['values'];
        } else {
            return [];
        }
    }

    /**
     * Retrieves an enum value.
     *
     * This method finds a enum string value. If ATTR_USE_NATIVE_ENUM is set
     * on the connection, index and value are the same thing.
     *
     * @param  string                $fieldName
     * @param  integer|Doctrine_Null $index     numeric index of the enum
     * @return mixed
     */
    public function enumValue($fieldName, $index)
    {
        if ($index instanceof Doctrine_Null) {
            return false;
        }

        if ($this->connection->getAttribute(Doctrine_Core::ATTR_USE_NATIVE_ENUM)) {
            return $index;
        }

        $columnName = $this->getColumnName($fieldName);

        return isset($this->columns[$columnName]['values'][$index]) ? $this->columns[$columnName]['values'][$index] : false;
    }

    /**
     * Retrieves an enum index.
     *
     * @see enumValue()
     *
     * @param  mixed  $value value of the enum considered
     * @return integer|string|null can be string if native enums are used.
     */
    public function enumIndex(string $fieldName, $value): int|string|null
    {
        $values = $this->getEnumValues($fieldName);

        if ($this->connection->getAttribute(Doctrine_Core::ATTR_USE_NATIVE_ENUM)) {
            return $value;
        }
        $res = array_search($value, $values);
        return $res === false ? null : $res;
    }

    /**
     * Validates a given field using table ATTR_VALIDATE rules.
     *
     * @see Doctrine_Core::ATTR_VALIDATE
     *
     * @param string|Doctrine_Record|Doctrine_Null $value
     * @param Doctrine_Record|null $record
     *
     * @phpstan-param T $record
     */
    public function validateField(string $fieldName, $value, Doctrine_Record $record = null): Doctrine_Validator_ErrorStack
    {
        if ($record instanceof Doctrine_Record) {
            $errorStack = $record->getErrorStack();
        } else {
            $record     = $this->create();
            $errorStack = new Doctrine_Validator_ErrorStack($this->name);
        }

        if ($value === Doctrine_Null::instance()) {
            $value = null;
        } elseif ($value instanceof Doctrine_Record && $value->exists()) {
            $value = $value->getIncremented();
        } elseif ($value instanceof Doctrine_Record && !$value->exists()) {
            foreach ($this->getRelations() as $relation) {
                // @phpstan-ignore-next-line
                if ($fieldName == $relation->getLocalFieldName() && (get_class($value) === $relation->getClass() || is_subclass_of($value, $relation->getClass()))) {
                    return $errorStack;
                }
            }
        }

        $dataType = $this->requireTypeOf($fieldName);

        // Validate field type, if type validation is enabled
        if ($this->getAttribute(Doctrine_Core::ATTR_VALIDATE) & Doctrine_Core::VALIDATE_TYPES) {
            if (!Doctrine_Validator::isValidType($value, $dataType)) {
                $errorStack->add($fieldName, 'type');
            }
            if ($dataType == 'enum') {
                $enumIndex = $this->enumIndex($fieldName, $value);
                if ($enumIndex === null && $value !== null) {
                    $errorStack->add($fieldName, 'enum');
                }
            }
            if ($dataType == 'set') {
                $values = $this->columns[$fieldName]['values'];
                // Convert string to array
                if (is_string($value)) {
                    $value = $value ? explode(',', $value) : [];
                    $value = array_map('trim', $value);
                    $record->set($fieldName, $value);
                }
                // Make sure each set value is valid
                if (is_iterable($value)) {
                    foreach ($value as $k => $v) {
                        if (!in_array($v, $values)) {
                            $errorStack->add($fieldName, 'set');
                        }
                    }
                }
            }
        }

        // Validate field length, if length validation is enabled
        if ($this->getAttribute(Doctrine_Core::ATTR_VALIDATE) & Doctrine_Core::VALIDATE_LENGTHS
            && is_string($value)
            && !Doctrine_Validator::validateLength($value, $dataType, $this->getFieldLength($fieldName))
        ) {
            $errorStack->add($fieldName, 'length');
        }

        // Run all custom validators
        $validators = $this->getFieldValidators($fieldName);

        // Skip rest of validation if value is allowed to be null
        if ($value === null && !isset($validators['notnull']) && !isset($validators['notblank'])) {
            return $errorStack;
        }

        foreach ($validators as $validatorName => $options) {
            if (!is_string($validatorName)) {
                $validatorName = $options;
                $options = [];
            }

            $validator = Doctrine_Validator::getValidator($validatorName);
            if (!empty($options) && $validator instanceof AbstractValidator) {
                $validator->setOptions($options);
            }
            if (!$validator->isValid($value)) {
                $errorStack->add($fieldName, $validator);
            }
        }

        return $errorStack;
    }

    public function getColumnCount(): int
    {
        return $this->columnCount;
    }

    /**
     * Retrieves all columns of the table.
     *
     * @see    $columns;
     * @return array<string,array<string,mixed>>    keys are column names and values are definition
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Removes a field name from the table schema information.
     *
     * @param  string $fieldName
     * @return boolean      true if the field is found and removed.
     *                      False otherwise.
     */
    public function removeColumn($fieldName)
    {
        if (!$this->hasField($fieldName)) {
            return false;
        }

        $columnName = $this->getColumnName($fieldName);
        unset($this->columnNames[$fieldName], $this->fieldNames[$columnName], $this->columns[$columnName]);
        $this->columnCount = count($this->columns);
        return true;
    }

    /**
     * Returns an array containing all the column names.
     *
     * @param  string[]|null $fieldNames
     * @return string[] numeric array
     */
    public function getColumnNames(array $fieldNames = null)
    {
        if ($fieldNames === null) {
            return array_keys($this->columns);
        } else {
            $columnNames = [];
            foreach ($fieldNames as $fieldName) {
                $columnNames[] = $this->getColumnName($fieldName);
            }
            return $columnNames;
        }
    }

    /**
     * Returns an array with all the identifier column names.
     *
     * @return string[] numeric array
     */
    public function getIdentifierColumnNames()
    {
        return $this->getColumnNames((array) $this->getIdentifier());
    }

    /**
     * Gets the array of unique fields sets.
     *
     * @see $uniques;
     *
     * @return mixed[] numeric array
     */
    public function getUniques()
    {
        return $this->uniques;
    }

    /**
     * Returns an array containing all the field names.
     *
     * @return string[] numeric array
     */
    public function getFieldNames()
    {
        return array_values($this->fieldNames);
    }

    /**
     * Retrieves the definition of a field.
     *
     * This method retrieves the definition of the column, basing of $fieldName
     * which can be a column name or a field name (alias).
     *
     * @return array<string,mixed>|null null on failure
     */
    public function getDefinitionOf(string $fieldName): ?array
    {
        $columnName = $this->getColumnName($fieldName);
        return $this->getColumnDefinition($columnName);
    }

    /**
     * Retrieves the type of a field.
     *
     * @return string|null null on failure
     */
    public function getTypeOf(string $fieldName): ?string
    {
        return $this->getTypeOfColumn($this->getColumnName($fieldName));
    }

    /**
     * Retrieves the type of a field.
     */
    public function requireTypeOf(string $fieldName): string
    {
        $type = $this->getTypeOfColumn($this->getColumnName($fieldName));
        if ($type === null) {
            throw new Doctrine_Table_Exception("Column $fieldName doesn't exist.");
        }
        return $type;
    }

    /**
     * Retrieves the type of a column.
     *
     * @return string|null null if column is not found
     */
    public function getTypeOfColumn(string $columnName): ?string
    {
        return $this->columns[$columnName]['type'] ?? null;
    }

    /**
     * Doctrine uses this function internally.
     * Users are strongly discouraged to use this function.
     *
     * @access private
     * @param  mixed[] $data internal data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }

    /**
     * Returns internal data.
     *
     * This method is used by Doctrine_Record instances
     * when retrieving data from database.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Performs special data preparation.
     *
     * This method returns a representation of a field data, depending on
     * the type of the given column.
     *
     * 1. It unserializes array and object typed columns
     * 2. Uncompresses gzip typed columns
     * 3. Initializes special null object pointer for null values (for fast column existence checking purposes)
     *
     * example:
     * <code type='php'>
     * $field = 'name';
     * $value = null;
     * $table->prepareValue($field, $value); // Doctrine_Null
     * </code>
     *
     * @throws Doctrine_Table_Exception     if unserialization of array/object typed column fails or
     * @throws Doctrine_Table_Exception     if uncompression of gzip typed column fails         *
     * @param  string                    $fieldName the name of the field
     * @param  string|null|Doctrine_Null $value     field value
     * @param  string|null               $typeHint  Type hint used to pass in the type of the value to prepare
     *                                              if it is already known. This enables the method to skip
     *                                              the type determination. Used i.e. during hydration.
     * @return mixed            prepared value
     */
    public function prepareValue(string $fieldName, $value, $typeHint = null)
    {
        if ($value === Doctrine_Null::instance()) {
            return $value;
        } elseif ($value === null) {
            return null;
        } else {
            $type = $typeHint ?? $this->getTypeOf($fieldName);

            switch ($type) {
                case 'enum':
                case 'integer':
                case 'string':
                    // don't do any casting here PHP INT_MAX is smaller than what the databases support
                    break;
                case 'set':
                    return $value ? explode(',', $value) : [];
                case 'boolean':
                    return (boolean) $value;
                case 'array':
                case 'object':
                    if (is_string($value)) {
                        $value = empty($value) ? null:unserialize($value);

                        if ($value === false) {
                            throw new Doctrine_Table_Exception('Unserialization of ' . $fieldName . ' failed.');
                        }
                        return $value;
                    }
                    break;
                case 'gzip':
                    $value = gzuncompress($value);

                    if ($value === false) {
                        throw new Doctrine_Table_Exception('Uncompressing of ' . $fieldName . ' failed.');
                    }
                    return $value;
            }
        }
        return $value;
    }

    /**
     * Gets the subclass of Doctrine_Record that belongs to this table.
     *
     * @psalm-return   class-string
     * @phpstan-return class-string<T>
     */
    public function getComponentName(): string
    {
        return $this->name;
    }

    /**
     * Gets the table name in the db.
     */
    public function getTableName(): string
    {
        return $this->tableName;
    }

    /**
     * sets the table name in the schema definition.
     */
    public function setTableName(string $tableName): void
    {
        $this->tableName = $this->connection->formatter->getTableName($tableName);
    }

    /**
     * Binds query parts to this component.
     *
     * @see bindQueryPart()
     *
     * @param  array $queryParts an array of pre-bound query parts
     * @return $this this object
     */
    public function bindQueryParts(array $queryParts): self
    {
        $this->queryParts = $queryParts;
        return $this;
    }

    /**
     * Adds default query parts to the selects executed on this table.
     *
     * This method binds given value to given query part.
     * Every query created by this table will have this part set by default.
     *
     * @param  string $queryPart
     * @param  mixed  $value
     * @return $this this object
     */
    public function bindQueryPart($queryPart, $value): self
    {
        $this->queryParts[$queryPart] = $value;
        return $this;
    }

    /**
     * Gets the names of all validators being applied on a field.
     *
     * @return array<string, mixed[]> names of validators
     */
    public function getFieldValidators(string $fieldName): array
    {
        $validators = [];
        $columnName = $this->getColumnName($fieldName);
        // this loop is a dirty workaround to get the validators filtered out of
        // the options, since everything is squeezed together currently
        foreach ($this->columns[$columnName] as $name => $args) {
            if (empty($name) || in_array($name, [
                'primary',
                'protected',
                'autoincrement',
                'default',
                'values',
                'sequence',
                'zerofill',
                'owner',
                'scale',
                'type',
                'length',
                'fixed',
                'comment',
                'alias',
                'extra',
                'virtual',
                'meta',
                'unique',
            ])) {
                continue;
            }
            if ($name == 'notnull' && isset($this->columns[$columnName]['autoincrement'])
                && $this->columns[$columnName]['autoincrement'] === true
            ) {
                continue;
            }
            // skip it if it's explicitly set to FALSE (i.e. notnull => false)
            if ($args === false) {
                continue;
            }
            $validators[$name] = $args === true ? [] : (array) $args;
        }

        return $validators;
    }

    /**
     * Gets the maximum length of a field.
     * For integer fields, length is bytes occupied.
     * For decimal fields, it is the total number of cyphers
     */
    public function getFieldLength(string $fieldName): ?int
    {
        return $this->columns[$this->getColumnName($fieldName)]['length'];
    }

    /**
     * Retrieves a bound query part.
     *
     * @see bindQueryPart()
     *
     * @param  string $queryPart field interested
     * @return string|null value of the bind
     */
    public function getBoundQueryPart(string $queryPart): ?string
    {
        return $this->queryParts[$queryPart] ?? null;
    }

    /**
     * @param  Doctrine_Record_Filter $filter
     * @return $this this object (provides a fluent interface)
     */
    public function unshiftFilter(Doctrine_Record_Filter $filter): self
    {
        $filter->setTable($this);

        $filter->init();

        array_unshift($this->filters, $filter);

        return $this;
    }

    /**
     * @return Doctrine_Record_Filter[] $filters
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Helper method for buildFindByWhere to decide if a string is greater than another
     */
    private function isGreaterThan(string $a, string $b): int
    {
        if (strlen($a) == strlen($b)) {
            return 0;
        }
        return (strlen($a) > strlen($b)) ? 1 : -1;
    }

    public function buildFindByWhere(string $fieldName): string
    {
        // Get all variations of possible field names
        $fields = array_merge($this->getFieldNames(), $this->getColumnNames());
        $fields = array_merge($fields, array_map(['Doctrine_Inflector', 'classify'], $fields));
        $fields = array_merge($fields, array_map('ucfirst', $fields));

        // Sort field names by length - smallest first
        // and then reverse so that largest is first
        usort($fields, [$this, 'isGreaterThan']);
        $fields = array_reverse(array_unique($fields));

        // Identify fields and operators
        preg_match_all('/(' . implode('|', $fields) . ')(Or|And)?/', $fieldName, $matches);
        $fieldsFound   = $matches[1];
        $operatorFound = array_map('strtoupper', $matches[2]);

        // Check if $fieldName has unidentified parts left
        if (strlen(implode('', $fieldsFound) . implode('', $operatorFound)) !== strlen($fieldName)) {
            $expression = preg_replace('/(' . implode('|', $fields) . ')(Or|And)?/', '($1)$2', $fieldName);
            throw new Doctrine_Table_Exception('Invalid expression found: ' . $expression);
        }

        // Build result
        $where       = $lastOperator       = '';
        $bracketOpen = false;
        foreach ($fieldsFound as $index => $field) {
            $field = $this->resolveFindByFieldName($field);
            if (!$field) {
                throw new Doctrine_Table_Exception('Invalid field name to find by: ' . $field);
            }

            if ($operatorFound[$index] == 'OR' && !$bracketOpen) {
                $where .= '(';
                $bracketOpen = true;
            }

            $where .= 'dctrn_find.' . $field . ' = ?';

            if ($operatorFound[$index] != 'OR' && $lastOperator == 'OR') {
                $where .= ')';
                $bracketOpen = false;
            }

            $where .= ' ' . strtoupper($operatorFound[$index]) . ' ';

            $lastOperator = $operatorFound[$index];
        }

        return trim($where);
    }

    /**
     * Resolves the passed find by field name inflecting the parameter.
     *
     * This method resolves the appropriate field name
     * regardless of whether the user passes a column name, field name, or a Doctrine_Inflector::classified()
     * version of their column name. It will be inflected with Doctrine_Inflector::tableize()
     * to get the column or field name.
     */
    protected function resolveFindByFieldName(string $name): ?string
    {
        $fieldName = Doctrine_Inflector::tableize($name);
        if ($this->hasColumn($name) || $this->hasField($name)) {
            return $this->getFieldName($this->getColumnName($name));
        } elseif ($this->hasColumn($fieldName) || $this->hasField($fieldName)) {
            return $this->getFieldName($this->getColumnName($fieldName));
        } else {
            return null;
        }
    }

    /**
     * Adds support for magic finders.
     *
     * This method add support for calling methods not defined in code, such as:
     * findByColumnName, findByRelationAlias
     * findById, findByContactId, etc.
     *
     * @param  string  $method
     * @param  mixed[] $arguments
     * @return mixed the result of the finder
     */
    public function __call($method, $arguments)
    {
        if (substr($method, 0, 6) == 'findBy') {
            $by     = substr($method, 6, strlen($method));
            $method = 'findBy';
        } elseif (substr($method, 0, 9) == 'findOneBy') {
            $by     = substr($method, 9, strlen($method));
            $method = 'findOneBy';
        }

        if (isset($by)) {
            if (!isset($arguments[0])) {
                throw new Doctrine_Table_Exception('You must specify the value to ' . $method);
            }

            // separate positional arguments from named ones
            $positional = [];
            $named = [];

            foreach ($arguments as $k => $v) {
                if (is_int($k)) {
                    $positional[] = $v;
                } else {
                    $named[$k] = $v;
                }
            }

            // options can only be passed with named arguments
            $hydrate_array = $named['hydrate_array'] ?? false;

            $fieldName = $this->resolveFindByFieldName($by);

            if ($fieldName !== null && $this->hasField($fieldName)) {
                return $this->$method($fieldName, $positional[0], $hydrate_array);
            }

            if ($this->hasRelation($by)) {
                $relation = $this->getRelation($by);

                if ($relation['type'] === Doctrine_Relation::MANY) {
                    throw new Doctrine_Table_Exception('Cannot findBy many relationship.');
                }

                return $this->$method($relation['local'], $positional[0], $hydrate_array);
            }

            return $this->$method($by, $positional, $hydrate_array);
        }

        // Forward the method on to the record instance and see if it has anything or one of its behaviors
        try {
            $method .= 'TableProxy';
            return $this->getRecordInstance()->$method(...$arguments);
        } catch (Doctrine_Record_UnknownPropertyException $e) {
        }

        throw new Doctrine_Table_Exception(sprintf('Unknown method %s::%s', get_class($this), $method));
    }
}
