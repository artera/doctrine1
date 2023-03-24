<?php

namespace Doctrine1;

use BackedEnum;
use Closure;
use Doctrine1\Column\Type;
use Doctrine1\Serializer\WithSerializers;
use Doctrine1\Deserializer\WithDeserializers;
use Doctrine1\Deserializer;
use Laminas\Validator\AbstractValidator;
use ReflectionClass;
use ReflectionMethod;

/**
 * @phpstan-template T of Record
 *
 * @phpstan-type ExportableOptions = array{
 *   name: string,
 *   tableName: string,
 *   sequenceName: ?string,
 *   inheritanceMap: array<string, mixed>,
 *   enumMap: array,
 *   type: ?string,
 *   charset: ?string,
 *   collate: ?string,
 *   treeImpl: mixed,
 *   treeOptions: array,
 *   indexes: array{type?: string, fields?: string[]}[],
 *   parents: array,
 *   queryParts: array,
 *   subclasses: string[],
 *   orderBy: mixed,
 *   checks: array,
 *   primary: string[],
 *   foreignKeys: mixed[],
 * }
 */
class Table extends Configurable implements \Countable
{
    use WithSerializers;
    use WithDeserializers;

    /**
     * temporary data which is then loaded into Record::$data
     */
    protected array $data = [];

    /**
     * @var string[] $identifier   The field names of all fields that are part of the identifier/primary key
     */
    protected array $identifier = [];

    /**
     * @see Identifier constants
     * the type of identifier this table uses
     */
    protected ?IdentifierType $identifierType = null;

    /**
     * Connection object that created this table
     */
    protected Connection $connection;

    /**
     * first level cache
     */
    protected array $identityMap = [];

    /**
     * record repository
     */
    protected ?Table\Repository $repository = null;

    /**
     * an array of column definitions,
     * keys are column names and values are column definitions
     *
     * @var Column[] $columns
     * @phpstan-var array<string, Column>
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
     * cached column count, Record uses this column count in when
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

    /**
     * inheritanceMap is used for inheritance mapping, keys representing columns and values
     * the column values that should correspond to child classes
     * @phpstan-var array<string, mixed>
     */
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

    /**
     * the parent classes of this component
     * @phpstan-var list<class-string<Record>>
     */
    public array $parents = [];
    public array $queryParts = [];
    /** @var string[] */
    public array $subclasses = [];
    public mixed $orderBy = null;
    public ?\ReflectionClass $declaringClass = null;

    /** Some databases need sequences instead of auto incrementation primary keys */
    public ?string $sequenceName = null;

    protected Relation\Parser $parser;

    /**
     * @see Record\Filter
     * an array containing all record filters attached to this table
     */
    protected array $filters = [];

    /**
     * empty instance of the given model
     * @phpstan-var T
     */
    protected ?Record $record = null;

    protected ?string $collectionKey = null;

    /**
     * the constructor
     *
     * @throws        Connection\Exception    if there are no opened connections
     * @param         string              $name           the name of the component
     * @phpstan-param class-string<T> $name
     * @param         Connection $conn           the connection associated with this table
     * @param         boolean             $initDefinition whether to init the in-memory schema
     */
    public function __construct(string $name, Connection $conn, $initDefinition = false)
    {
        $this->connection = $conn;
        $this->name = $name;

        $this->setParent($this->connection);
        $this->connection->addTable($this);

        $this->parser = new Relation\Parser($this);

        if ($charset = $this->getCharset()) {
            $this->charset = $charset;
        }
        if ($collate = $this->getCollate()) {
            $this->collate = $collate;
        }

        if ($initDefinition) {
            $this->record = $record = $this->initDefinition();
            $this->initIdentifier();
            $record->setUp();
        } elseif (empty($this->tableName)) {
            $this->setTableName(Inflector::tableize($this->name));
        }

        $this->filters[]  = new Record\Filter\Standard();
        $this->repository = new Table\Repository($this);

        $this->construct();
    }

    /**
     * Construct template method.
     *
     * This method provides concrete Table classes with the possibility
     * to hook into the constructor procedure. It is called after the
     * Table construction process is finished.
     *
     * @return void
     */
    public function construct()
    {
    }

    public function getCollectionKey(): ?string
    {
        return $this->collectionKey;
    }

    public function setCollectionKey(?string $value): void
    {
        if ($value !== null && !$this->hasField($value)) {
            throw new Exception("Couldn't set collection key attribute. No such field '$value'.");
        }
        $this->collectionKey = $value;
    }

    /**
     * Initializes the in-memory table definition.
     *
     * @return Record
     * @phpstan-return T
     */
    public function initDefinition(): Record
    {
        $class = $this->getComponentName();
        $record = new $class($this);
        assert($record instanceof Record);

        /** @phpstan-var list<class-string<Record>> */
        $parents = array_values(class_parents($class) ?: []);
        array_pop($parents);
        $this->parents = array_reverse($parents);

        $record->setTableDefinition();
        // get the declaring class of setTableDefinition method
        $method = new ReflectionMethod($record, 'setTableDefinition');
        $class = $method->getDeclaringClass();

        foreach ($parents as $parent) {
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

            foreach ($parentColumns as $column) {
                if (!$column->primary) {
                    if (isset($this->columns[$column->name])) {
                        $found = true;
                        break;
                    } else {
                        $parentColumns[$column->name]->owner ??= $parentTable->getComponentName();
                    }
                } else {
                    unset($parentColumns[$column->name]);
                }
            }

            if ($found) {
                continue;
            }

            foreach ($parentColumns as $column) {
                $fullName = $column->name . ' as ' . $parentTable->getFieldName($column->name);
                $this->setColumn($column->modify(['name' => $fullName]), true);
            }

            break;
        }

        $this->declaringClass = $class;

        $this->columnCount = count($this->columns);

        if (empty($this->tableName)) {
            $this->setTableName(Inflector::tableize($class->getName()));
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
     * @return IdentifierType the identifier type
     */
    public function initIdentifier(): IdentifierType
    {
        $id_count = count($this->identifier);

        if ($id_count > 1) {
            $this->identifierType = IdentifierType::Composite;
            return $this->identifierType;
        }

        if ($id_count == 1) {
            foreach ($this->identifier as $pk) {
                $e = $this->getDefinitionOf($pk);

                if (!$e) {
                    continue;
                }

                if ($e->autoincrement !== false || $e->sequence !== null) {
                    $this->identifierType = IdentifierType::Autoinc;

                    if ($e->sequence !== null) {
                        $this->sequenceName = $e->sequence;
                    }
                }

                if (!isset($this->identifierType)) {
                    $this->identifierType = IdentifierType::Natural;
                }
            }

            if (isset($pk)) {
                if (!isset($this->identifierType)) {
                    $this->identifierType = IdentifierType::Natural;
                }
                $this->identifier = [$pk];
                return $this->identifierType;
            }
        }

        $identifierOptions = $this->getDefaultIdentifierOptions();
        $name = empty($identifierOptions['name']) ? 'id' : $identifierOptions['name'];
        /** @var non-empty-string $name */
        $name = sprintf($name, $this->getTableName());

        $this->setColumn(new Column(
            $name,
            type: empty($identifierOptions['type']) ? Column\Type::Integer : $identifierOptions['type'],
            length: empty($identifierOptions['length']) ? 8 : $identifierOptions['length'],
            primary: $identifierOptions['primary'] ?? true,
            autoincrement: isset($identifierOptions['autoincrement']) ? $identifierOptions['autoincrement'] : true,
        ), true);
        $this->identifier = [$name];
        $this->identifierType = IdentifierType::Autoinc;

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
     * @return string             the name of the owning/defining component
     */
    public function getColumnOwner(string $columnName): string
    {
        return $this->columns[$columnName]?->owner ?? $this->getComponentName();
    }

    /**
     * Gets the record instance for this table.
     *
     * The Table instance always holds at least one
     * instance of a model so that it can be reused for several things,
     * but primarily it is first used to instantiate all the internal
     * in memory schema definition.
     *
     * @return Record  Empty instance of the record
     * @phpstan-return T
     */
    public function getRecordInstance(): Record
    {
        if ($this->record === null) {
            $this->record = new ($this->getComponentName())();
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
        return isset($this->columns[$columnName]->owner);
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
        return $this->getIdentifierType() === IdentifierType::Autoinc;
    }

    /**
     * Checks whether a field identifier is a composite key.
     *
     * @return boolean TRUE  if the identifier is a composite key,
     *                 FALSE otherwise
     */
    public function isIdentifierComposite()
    {
        return $this->getIdentifierType() === IdentifierType::Composite;
    }

    /**
     * Exports this table to database based on the schema definition.
     *
     * This method create a physical table in the database, using the
     * definition that comes from the component Record instance.
     *
     * @throws Connection\Exception    if some error other than Core::ERR_ALREADY_EXISTS
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
     *   columns: array<string, Column>,
     *   options: ExportableOptions,
     * }
     */
    public function getExportableFormat($parseForeignKeys = true)
    {
        $columns = [];
        $primary = [];

        foreach ($this->getColumns() as $column) {
            if ($column->owner !== null) {
                continue;
            }

            if ($column->type === Column\Type::Boolean && $column->hasDefault()) {
                $column->default = $this->getConnection()->convertBooleans($column->default);
            }

            if ($column->primary) {
                $primary[] = $column->name;
            }

            $columns[$column->name] = $column;
        }

        $options = $this->getOptions();
        $options['foreignKeys'] = [];

        if ($parseForeignKeys && $this->getExportFlags() & Core::EXPORT_CONSTRAINTS) {
            $constraints = [];

            $emptyIntegrity = [
                'onUpdate' => null,
                'onDelete' => null,
            ];

            foreach ($this->getRelations() as $name => $relation) {
                $fk = $relation->toArray();
                $fk['foreignTable'] = $relation->getTable()->getTableName();

                // do not touch tables that have EXPORT_NONE attribute
                if ($relation->getTable()->getExportFlags() === Core::EXPORT_NONE) {
                    continue;
                }

                if ($relation->getTable() === $this && in_array($relation->getLocal(), $primary)) {
                    if ($relation->hasConstraint()) {
                        throw new Table\Exception('Badly constructed integrity constraints. Cannot define constraint of different fields in the same table.');
                    }
                    continue;
                }

                $integrity = [
                    'onUpdate' => $fk['onUpdate'],
                    'onDelete' => $fk['onDelete']
                ];

                $fkName = $relation->getForeignKeyName();

                if ($relation instanceof Relation\LocalKey) {
                    $def = [
                        'name'         => $fkName,
                        'local'        => $relation->getLocalColumnName(),
                        'foreign'      => $relation->getForeignColumnName(),
                        'foreignTable' => $relation->getTable()->getTableName(),
                    ];

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
     * @return Relation\Parser     relation parser object
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
     *   inheritanceMap: array<string, mixed>,
     *   enumMap: array,
     *   type: ?string,
     *   charset: ?string,
     *   collate: ?string,
     *   treeImpl: mixed,
     *   treeOptions: array,
     *   indexes: array,
     *   parents: array,
     *   queryParts: array,
     *   subclasses: string[],
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
     * @param string $index      index name
     * @param array $definition keys are type, fields
     * @phpstan-param array{type?: string, fields?: string[]} $definition
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
     * @param  bool     $createUniqueIndex Whether or not to create a unique index in the database
     * @return void
     */
    public function unique(array $fields, array $options = [], bool $createUniqueIndex = true): void
    {
        if ($createUniqueIndex) {
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
     * @see    Relation::_$definition
     * @param  integer $type Relation::ONE or Relation::MANY
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
     * @see    Relation::_$definition
     * @return void
     */
    public function hasOne(...$args)
    {
        $this->bind($args, Relation::ONE);
    }

    /**
     * Binds One-to-Many / Many-to-Many aggregate relation
     *
     * @param  mixed ...$args first value is a string, name of related component;
     *                        second value is array, options for the relation.
     * @see    Relation::_$definition
     * @return void
     */
    public function hasMany(...$args)
    {
        $this->bind($args, Relation::MANY);
    }

    /**
     * Tests if a relation exists.
     *
     * This method queries the table definition to find out if a relation
     * is defined for this component. Alias defined with foreignAlias are not
     * recognized as there's only one Relation object on the owning
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
     * @return Relation
     */
    public function getRelation(string $alias, bool $recursive = true): Relation
    {
        return $this->parser->getRelation($alias, $recursive);
    }

    /**
     * Retrieves all relation objects defined on this table.
     *
     * @phpstan-return Relation[]
     * @return array
     */
    public function getRelations()
    {
        return $this->parser->getRelations();
    }

    /**
     * Creates a query on this table.
     *
     * This method returns a new Query object and adds the component
     * name of this table as the query 'from' part.
     * <code>
     * $table = Core::getTable('User');
     * $table->createQuery('myuser')
     *       ->where('myuser.Phonenumber = ?', '5551234');
     * </code>
     *
     * @param string $alias name for component aliasing
     *
     * @return Query
     *
     * @phpstan-return Query<T, Query\Type\Select>
     */
    public function createQuery($alias = ''): Query
    {
        if (!empty($alias)) {
            $alias = ' ' . trim($alias);
        }

        $class = $this->getQueryClass();

        /** @phpstan-var Query<T, Query\Type\Select> */
        return Query::create(null, $class)
            ->from($this->getComponentName() . $alias);
    }

    /**
     * Gets the internal record repository.
     *
     * @return Table\Repository|null
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
    public function processOrderBy($alias, array|string $orderBy, $columnNames = false)
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
    public function getColumnName($fieldName, bool $caseSensitive = true): string
    {
        // FIX ME: This is being used in places where an array is passed, but it should not be an array
        // For example in places where Doctrine should support composite foreign/primary keys
        $fieldName = is_array($fieldName) ? $fieldName[0] : $fieldName;

        if (!$caseSensitive) {
            return Lib::arrayCIGet($fieldName, $this->columnNames) ?? strtolower($fieldName);
        }

        if (isset($this->columnNames[$fieldName])) {
            return $this->columnNames[$fieldName];
        }

        return strtolower($fieldName);
    }

    /**
     * Retrieves a column definition from this table schema.
     */
    public function getColumn(string $columnName): ?Column
    {
        return $this->columns[$columnName] ?? null;
    }

    /**
     * Retrieves a column definition from this table schema.
     */
    public function column(string $columnName): Column
    {
        if (!array_key_exists($columnName, $this->columns)) {
            throw new Table\Exception("Column $columnName doesn't exist.");
        }
        return $this->columns[$columnName];
    }

    /**
     * Returns a column alias for a column name.
     *
     * If no alias can be found the column name is returned.
     */
    public function getFieldName(string $columnName, bool $caseSensitive = true): string
    {
        if (!$caseSensitive) {
            return Lib::arrayCIGet($columnName, $this->fieldNames) ?? $columnName;
        }

        if (isset($this->fieldNames[$columnName])) {
            return $this->fieldNames[$columnName];
        }

        return $columnName;
    }

    /**
     * Set multiple column definitions at once
     *
     * @param Column[] $columns
     */
    public function setColumns(array $columns): void
    {
        foreach ($columns as $column) {
            $this->setColumn($column);
        }
    }

    /**
     * Adds a column to the schema.
     *
     * This method does not alter the database table; @see export();
     *
     * @see $columns;
     * @param boolean $prepend Whether to prepend or append the new column to the column list.
     *                         By default the column gets appended.
     * @throws Table\Exception if trying use wrongly typed parameter
     * @return void
     */
    public function setColumn(Column $column, $prepend = false)
    {
        if ($prepend) {
            $this->columnNames = array_merge([$column->fieldName => $column->name], $this->columnNames);
            $this->fieldNames  = array_merge([$column->name => $column->fieldName], $this->fieldNames);
        } else {
            $this->columnNames[$column->fieldName] = $column->name;
            $this->fieldNames[$column->name] = $column->fieldName;
        }

        if ($prepend) {
            $this->columns = array_merge([$column->name => $column], $this->columns);
        } else {
            $this->columns[$column->name] = $column;
        }

        if ($column->primary) {
            if (!in_array($column->fieldName, $this->identifier)) {
                $this->identifier[] = $column->fieldName;
            }
        }
        if ($column->hasDefault()) {
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
    public function getDefaultValueOf(string $fieldName)
    {
        $columnName = $this->getColumnName($fieldName, false);
        if (!isset($this->columns[$columnName])) {
            throw new Table\Exception("Couldn't get default value. Column $columnName doesn't exist.");
        }

        if ($this->columns[$columnName]->virtual || !$this->columns[$columnName]->hasDefault()) {
            return null;
        }

        $default = $this->columns[$columnName]->default;
        if ($default instanceof Closure) {
            $default = $default();
        }

        return $this->deserializeColumnValue($default, $fieldName);
    }

    /** @param Deserializer\DeserializerInterface[]|null $deserializers */
    public function deserializeColumnValue(mixed $value, string $fieldName, ?array $deserializers = null): mixed
    {
        $column = $this->getColumn($this->getColumnName($fieldName, false));
        if ($column === null || ($value === null && !$column->notnull)) {
            return $value;
        }
        if ($deserializers === null) {
            $deserializers = array_merge(
                Manager::getInstance()->getDeserializers(),
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
            throw new Table\Exception("Table has now identifiers");
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
    public function getIdentifierType(): ?IdentifierType
    {
        return $this->identifierType;
    }

    /**
     * Finds out whether the table definition contains a given column.
     */
    public function hasColumn(string $columnName, bool $caseSensitive = true): bool
    {
        if (!$caseSensitive) {
            foreach ($this->columns as $name => $_) {
                if (strtolower($name) === strtolower($columnName)) {
                    return true;
                }
            }
            return false;
        }
        return isset($this->columns[$columnName]);
    }

    /**
     * Finds out whether the table definition has a given field.
     *
     * This method returns true if @see hasColumn() returns true or if an alias
     * named $fieldName exists.
     */
    public function hasField(string $fieldName, bool $caseSensitive = true): bool
    {
        if (!$caseSensitive) {
            foreach ($this->columnNames as $name => $_) {
                if (strtolower($name) === strtolower($fieldName)) {
                    return true;
                }
            }
            return false;
        }
        return isset($this->columnNames[$fieldName]);
    }

    /**
     * Sets the default connection for this table.
     *
     * This method assign the connection which this table will use
     * to create queries.
     *
     * @params Connection      a connection object
     * @return $this                    this object; fluent interface
     */
    public function setConnection(Connection $conn)
    {
        $this->connection = $conn;

        $this->setParent($this->connection);

        return $this;
    }

    /**
     * Returns the connection associated with this table (if any).
     *
     * @return Connection     the connection object
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Creates a new record.
     *
     * This method create a new instance of the model defined by this table.
     * The class of this record is the subclass of Record defined by
     * this component. The record is not created in the database until you
     * call @save().
     *
     * @param array $array an array where keys are field names and
     *                              values representing field values. Can
     *                              contain also related components;
     *
     * @see Record::fromArray()
     *
     * @return Record
     *
     * @phpstan-return T
     */
    public function create(array $array = []): Record
    {
        /** @phpstan-var T $record */
        $record = new ($this->getComponentName())($this, true);
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
     * @param         string|Query $query    DQL string or object
     * @phpstan-param string|Query<T, Query\Type> $query
     * @return        void
     */
    public function addNamedQuery($queryKey, $query)
    {
        $registry = Manager::getInstance()->getQueryRegistry();
        $registry->add($this->getComponentName() . '/' . $queryKey, $query);
    }

    /**
     * Creates a named query from one in the query registry.
     *
     * This method clones a new query object from a previously registered one.
     *
     * @see            addNamedQuery()
     * @param          string $queryKey query key name
     * @return         Query
     * @phpstan-return Query<T, Query\Type>
     */
    public function createNamedQuery($queryKey)
    {
        $queryRegistry = Manager::getInstance()->getQueryRegistry();

        if (strpos($queryKey, '/') !== false) {
            $e = explode('/', $queryKey);

            return $queryRegistry->get($e[1], $e[0]);
        }

        return $queryRegistry->get($queryKey, $this->getComponentName());
    }

    /**
     * @phpstan-return T|Collection<T>|array<string,mixed>|null
     */
    public function find(array|int|string $params = [], bool $hydrateArray = false, ?string $name = null): Collection|Record|array|null
    {
        $hydrationMode = $hydrateArray ? HydrationMode::Array : HydrationMode::Record;
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
                if (!Manager::getInstance()->getQueryRegistry()->has($name, $ns)) {
                    throw new Table\Exception("Could not find query named $name.");
                }

                $q = $this->createNamedQuery($name);
                /** @var Collection<T>|array<string,mixed>[]|T|array<string,mixed>|null */
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
     * @phpstan-return Collection<T>|array<string,mixed>[]
     */
    public function findAll(bool $hydrateArray = false): Collection|array
    {
        $hydrationMode = $hydrateArray ? HydrationMode::Array : HydrationMode::Record;
        /** @phpstan-var Collection<T>|array<string,mixed>[] */
        return $this->createQuery('dctrn_find')->execute([], $hydrationMode);
    }

    /**
     * Finds records in this table with a given SQL where clause.
     *
     * @param          string $dql           DQL WHERE clause to use
     * @param          array  $params        query parameters (a la PDO)
     * @phpstan-return Collection<T>|array<string,mixed>[]
     */
    public function findBySql($dql, $params = [], bool $hydrateArray = false): Collection|array
    {
        $hydrationMode = $hydrateArray ? HydrationMode::Array : HydrationMode::Record;
        /** @phpstan-var Collection<T>|array<string,mixed>[] */
        return $this->createQuery('dctrn_find')
            ->where($dql)->execute($params, $hydrationMode);
    }

    /**
     * Finds records in this table with a given DQL where clause.
     *
     * @param          string  $dql           DQL WHERE clause
     * @param          mixed[] $params        preparated statement parameters
     * @phpstan-return Collection<T>|array<string,mixed>[]
     */
    public function findByDql($dql, $params = [], bool $hydrateArray = false): Collection|array
    {
        $hydrationMode = $hydrateArray ? HydrationMode::Array : HydrationMode::Record;
        $parser = $this->createQuery();
        $query  = 'FROM ' . $this->getComponentName() . ' dctrn_find WHERE ' . $dql;

        /** @phpstan-var Collection<T>|array<string,mixed>[] */
        return $parser->query($query, $params, $hydrationMode);
    }

    /**
     * Find records basing on a field.
     *
     * @param          string $fieldName     field for the WHERE clause
     * @param          string $value         prepared statement parameter
     * @phpstan-return Collection<T>|array<string,mixed>[]
     */
    public function findBy($fieldName, $value, bool $hydrateArray = false): Collection|array
    {
        $hydrationMode = $hydrateArray ? HydrationMode::Array : HydrationMode::Record;
        /** @phpstan-var Collection<T>|array<string,mixed>[] */
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
    public function findOneBy($fieldName, $value, bool $hydrateArray = false): Record|array|null
    {
        $hydrationMode = $hydrateArray ? HydrationMode::Array : HydrationMode::Record;
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
     * @phpstan-return Collection<T>|array<string,mixed>[]
     */
    public function execute($queryKey, $params = [], bool $hydrateArray = false): Collection|array
    {
        $hydrationMode = $hydrateArray ? HydrationMode::Array : HydrationMode::Record;
        /** @phpstan-var Collection<T>|array<string,mixed>[] */
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
    public function executeOne($queryKey, $params = [], bool $hydrateArray = false): Record|array|null
    {
        $hydrationMode = $hydrateArray ? HydrationMode::Array : HydrationMode::Record;
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
     * @param Record $record
     *
     * @phpstan-param T $record
     *
     * @return boolean                      true if record was not present in the map
     *
     * @todo Better name? registerRecord?
     */
    public function addRecord(Record $record): bool
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
     * @param Record $record
     *
     * @phpstan-param T $record
     *
     * @return boolean                  true if the record was found and removed,
     *                                  false if the record wasn't found.
     */
    public function removeRecord(Record $record): bool
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
     * @return         Record
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
            if ($record->getTable()->getHydrateOverwrite() && !$record->state()->isLocked()) {
                $record->hydrate($this->data);
                if ($record->state() == Record\State::PROXY) {
                    if (!$record->isInProxyState()) {
                        $record->state(Record\State::CLEAN);
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
     * @param          string|int|null $id database row id
     * @phpstan-return ?T
     */
    final public function getProxy($id = null): ?Record
    {
        if ($id !== null) {
            $identifierColumnNames = $this->getIdentifierColumnNames();
            $query                 = 'SELECT ' . implode(', ', (array) $identifierColumnNames)
                . ' FROM ' . $this->getTableName()
                . ' WHERE ' . implode(' = ? && ', (array) $identifierColumnNames) . ' = ?';
            $query = $this->applyInheritance($query);

            $params = array_merge([$id], array_values($this->inheritanceMap));

            $this->data = $this->connection->execute($query, $params)->fetch(\PDO::FETCH_ASSOC);

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
    public function count(): int
    {
        return $this->createQuery()->count();
    }

    /**
     * @return Query
     *
     * @phpstan-return Query<T, Query\Type\Select>
     */
    public function getQueryObject(): Query
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
        $columnName = $this->getColumnName($fieldName, false);
        return $this->columns[$columnName]->stringValues();
    }

    /**
     * Retrieves an enum value.
     *
     * This method finds a enum string value. If setUseNativeEnum() is set
     * on the connection, index and value are the same thing.
     *
     * @param  string                $fieldName
     * @param  integer|None $index     numeric index of the enum
     * @return mixed
     */
    public function enumValue($fieldName, $index)
    {
        if ($index instanceof None) {
            return false;
        }

        if ($this->connection->getUseNativeEnum()) {
            return $index;
        }

        $columnName = $this->getColumnName($fieldName, false);
        $values = $this->columns[$columnName]->stringValues();

        return isset($values[$index]) ? $values[$index] : false;
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

        if ($this->connection->getUseNativeEnum()) {
            return $value;
        }
        $res = array_search($value, $values);
        return $res === false ? null : $res;
    }

    /**
     * Validates a given field using table getValidate() rules.
     *
     * @param string|Record|None $value
     * @param Record|null $record
     *
     * @phpstan-param T $record
     */
    public function validateField(string $fieldName, $value, Record $record = null): Validator\ErrorStack
    {
        if ($record instanceof Record) {
            $errorStack = $record->getErrorStack();
        } else {
            $record     = $this->create();
            $errorStack = new Validator\ErrorStack($this->name);
        }

        $columnName = $this->getColumnName($fieldName, false);
        $column = $this->column($columnName);

        // ignore validation of virtual columns
        if ($column->virtual) {
            return $errorStack;
        }

        if ($value === None::instance()) {
            $value = null;
        } elseif ($value instanceof Record && $value->exists()) {
            $value = $value->getIncremented();
        } elseif ($value instanceof Record && !$value->exists()) {
            foreach ($this->getRelations() as $relation) {
                // @phpstan-ignore-next-line
                if ($fieldName == $relation->getLocalFieldName() && (get_class($value) === $relation->getClass() || is_subclass_of($value, $relation->getClass()))) {
                    return $errorStack;
                }
            }
        }

        // Validate field type, if type validation is enabled
        if ($this->getValidate() & Core::VALIDATE_TYPES) {
            if (!Validator::isValidType($value, $column->type)) {
                $errorStack->add($fieldName, 'type');
            }
            if ($column->type === Type::Enum) {
                if ($value instanceof BackedEnum) {
                    if (!in_array($value, $column->values(), true)) {
                        $errorStack->add($fieldName, 'enum');
                    }
                } elseif ($value !== null) {
                    if (!in_array($value, $column->stringValues(), true)) {
                        $errorStack->add($fieldName, 'enum');
                    }
                }
            }
            if ($column->type === Type::Set) {
                $values = $column->values();
                $stringValues = $column->stringValues();

                // Convert string to array
                if (is_string($value)) {
                    $value = $value ? explode(',', $value) : [];
                    $value = array_map('trim', $value);
                    $record->set($fieldName, $value);
                }
                // Make sure each set value is valid
                if (is_iterable($value)) {
                    foreach ($value as $k => $v) {
                        if ($v instanceof BackedEnum) {
                            if (!in_array($v, $values, true)) {
                                $errorStack->add($fieldName, 'enum');
                            }
                        } elseif ($v !== null) {
                            if (!in_array($v, $stringValues, true)) {
                                $errorStack->add($fieldName, 'enum');
                            }
                        }
                    }
                }
            }
        }

        // Validate field length, if length validation is enabled
        if (
            $this->getValidate() & Core::VALIDATE_LENGTHS
            && is_string($value)
            && !Validator::validateLength($value, $column->type, $column->length)
        ) {
            $errorStack->add($fieldName, 'length');
        }

        // Run all custom validators
        $validators = $column->getValidators();

        // Skip rest of validation for empty autoincrement int columns
        if ($column->type === Type::Integer && $column->autoincrement && empty($value) && !is_numeric($value)) {
            return $errorStack;
        }

        // Skip rest of validation if value is allowed to be null
        if ($value === null && !isset($validators['notnull']) && !isset($validators['notblank'])) {
            return $errorStack;
        }

        foreach ($validators as $validatorName => $options) {
            $validator = Validator::getValidator($validatorName);
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
     * @return Column[] keys are column names and values are definition
     * @phpstan-return array<string, Column>
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

        $columnName = $this->getColumnName($fieldName, false);
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
    public function getColumnNames(array $fieldNames = null): array
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
    public function getIdentifierColumnNames(): array
    {
        return $this->getColumnNames((array) $this->getIdentifier());
    }

    /**
     * Returns an array with all the identifier column names.
     *
     * @return Column[] numeric array
     */
    public function getIdentifierColumns(): array
    {
        return array_filter(array_map(fn ($name) => $this->getColumn($name), $this->getIdentifierColumnNames()));
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
     */
    public function getDefinitionOf(string $fieldName): ?Column
    {
        $columnName = $this->getColumnName($fieldName, false);
        return $this->getColumn($columnName);
    }

    /**
     * Retrieves the type of a field.
     *
     * @return Type|null null on failure
     */
    public function getTypeOf(string $fieldName): ?Type
    {
        return $this->getTypeOfColumn($this->getColumnName($fieldName, false));
    }

    /**
     * Retrieves the type of a field.
     */
    public function requireTypeOf(string $fieldName): Type
    {
        $type = $this->getTypeOfColumn($this->getColumnName($fieldName, false));
        if ($type === null) {
            throw new Table\Exception("Column $fieldName doesn't exist.");
        }
        return $type;
    }

    /**
     * Retrieves the type of a column.
     *
     * @return Type|null null if column is not found
     */
    public function getTypeOfColumn(string $columnName): ?Type
    {
        return $this->columns[$columnName]->type ?? null;
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
     * This method is used by Record instances
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
     * 3. Initializes special null object pointer for null values (for fast column existence checking purposes)
     *
     * example:
     * <code type='php'>
     * $field = 'name';
     * $value = null;
     * $table->prepareValue($field, $value); // None
     * </code>
     *
     * @throws Table\Exception     if unserialization of array/object typed column fails or
     * @param  string                    $fieldName the name of the field
     * @param  string|null|None $value     field value
     * @param  Type|null               $typeHint  Type hint used to pass in the type of the value to prepare
     *                                              if it is already known. This enables the method to skip
     *                                              the type determination. Used i.e. during hydration.
     * @return mixed            prepared value
     */
    public function prepareValue(string $fieldName, $value, ?Type $typeHint = null)
    {
        if ($value === None::instance()) {
            return $value;
        } elseif ($value === null) {
            return null;
        } else {
            $type = $typeHint ?? $this->getTypeOf($fieldName);

            switch ($type) {
                case Type::Set:
                    return $value ? explode(',', $value) : [];
                case Type::Boolean:
                    return (bool) $value;
                case Type::Array:
                case Type::Object:
                    if (is_string($value)) {
                        $value = empty($value) ? null : unserialize($value);

                        if ($value === false) {
                            throw new Table\Exception('Unserialization of ' . $fieldName . ' failed.');
                        }
                        return $value;
                    }
                    break;
            }
        }
        return $value;
    }

    /**
     * Gets the subclass of Record that belongs to this table.
     *
     * @phpstan-return class-string<T>
     */
    public function getComponentName(): string
    {
        $name = $this->name;

        if (!class_exists($name) && strpos($name, '\\') === false) {
            $name = Lib::namespaceConcat($this->getModelNamespace(), $name);
        }

        /** @phpstan-var class-string<T> $name */
        return $name;
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
        $this->tableName = $tableName;
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
     * @param  Record\Filter $filter
     * @return $this this object (provides a fluent interface)
     */
    public function unshiftFilter(Record\Filter $filter): self
    {
        $filter->setTable($this);

        $filter->init();

        array_unshift($this->filters, $filter);

        return $this;
    }

    /**
     * @return Record\Filter[] $filters
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
        $fields = array_merge($fields, array_map([Inflector::class, 'classify'], $fields));
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
            throw new Table\Exception('Invalid expression found: ' . $expression);
        }

        // Build result
        $where       = $lastOperator       = '';
        $bracketOpen = false;
        foreach ($fieldsFound as $index => $field) {
            $field = $this->resolveFindByFieldName($field);
            if (!$field) {
                throw new Table\Exception('Invalid field name to find by: ' . $field);
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
     * regardless of whether the user passes a column name, field name, or a Inflector::classified()
     * version of their column name. It will be inflected with Inflector::tableize()
     * to get the column or field name.
     */
    protected function resolveFindByFieldName(string $name): ?string
    {
        $fieldName = Inflector::tableize($name);
        if ($this->hasColumn($name, false) || $this->hasField($name, false)) {
            return $this->getFieldName($this->getColumnName($name, false));
        } elseif ($this->hasColumn($fieldName, false) || $this->hasField($fieldName, false)) {
            return $this->getFieldName($this->getColumnName($fieldName, false));
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
                throw new Table\Exception('You must specify the value to ' . $method);
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
            $hydrateArray = $named['hydrateArray'] ?? false;

            $fieldName = $this->resolveFindByFieldName($by);

            if ($fieldName !== null && $this->hasField($fieldName)) {
                return $this->$method($fieldName, $positional[0], $hydrateArray);
            }

            if ($this->hasRelation($by)) {
                $relation = $this->getRelation($by);

                if ($relation['type'] === Relation::MANY) {
                    throw new Table\Exception('Cannot findBy many relationship.');
                }

                return $this->$method($relation['local'], $positional[0], $hydrateArray);
            }

            return $this->$method($by, $positional, $hydrateArray);
        }

        // Forward the method on to the record instance and see if it has anything or one of its behaviors
        try {
            $method .= 'TableProxy';
            return $this->getRecordInstance()->$method(...$arguments);
        } catch (Record\UnknownPropertyException $e) {
        }

        throw new Table\Exception(sprintf('Unknown method %s::%s', get_class($this), $method));
    }
}
