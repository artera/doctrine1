<?php

namespace Doctrine1;

/**
 * @phpstan-type RelationDefinition = array{
 *   alias: string,
 *   foreign: string,
 *   local: string,
 *   class: class-string<Record>,
 *   type: int,
 *   table: Table,
 *   localTable: Table,
 *   name: ?string,
 *   refTable: ?Table,
 *   onDelete: ?string,
 *   onUpdate: ?string,
 *   deferred: ?bool,
 *   deferrable: ?bool,
 *   constraint: ?bool,
 *   equal: bool,
 *   cascade: string[],
 *   owningSide: bool,
 *   refClassRelationAlias: ?string,
 *   foreignKeyName: ?string,
 *   orderBy: null|string|string[],
 * }
 */
abstract class Relation implements \ArrayAccess
{
    /**
     * RELATION CONSTANTS
     */

    /**
     * constant for ONE_TO_ONE and MANY_TO_ONE relationships
     */
    public const ONE = 0;

    /**
     * constant for MANY_TO_MANY and ONE_TO_MANY relationships
     */
    public const MANY = 1;

    // TRUE => mandatory, everything else is just a default value. this should be refactored
    // since TRUE can bot be used as a default value this way. All values should be default values.
    /** @phpstan-var RelationDefinition */
    protected array $definition;

    protected ?bool $isRefClass = null;

    /**
     * constructor
     *
     * @param array $definition an associative array with the following structure:
     * name foreign key constraint name
     * local the local field(s)
     * foreign the foreign reference field(s)
     * table the foreign table object
     * localTable the local table object
     * refTable the reference table object  (if any)
     * onDelete referential delete action
     * onUpdate referential update action
     * deferred * deferred constraint checking
     * alias relation alias
     * type the relation type, either Relation::ONE or Relation::MANY
     * constraint boolean value, true if the relation has an explicit referential integrity constraint
     * foreignKeyName the name of the dbms foreign key to create.
     *
     * Optional, if left blank Doctrine will generate one for you The onDelete
     * and onUpdate keys accept the following values:
     * CASCADE: Delete or update the row from the parent
     * table and automatically delete or update the
     * matching rows in the child table. Both ON DELETE
     * CASCADE and ON UPDATE CASCADE are supported.
     * Between two tables, you should not define several
     * ON UPDATE CASCADE clauses that act on the same
     * column in the parent table or in the child table.
     * SET NULL: Delete or update the row from the parent
     * table and set the foreign key column or columns in
     * the child table to NULL. This is valid only if the
     * foreign key columns do not have the NOT NULL
     * qualifier specified. Both ON DELETE SET NULL and
     * ON UPDATE SET NULL clauses are supported. NO
     * ACTION: In standard SQL, NO ACTION means no action
     * in the sense that an attempt to delete or update a
     * primary key value is not allowed to proceed if
     * there is a related foreign key value in the
     * referenced table. RESTRICT: Rejects the delete or
     * update operation for the parent table. NO ACTION
     * and RESTRICT are the same as omitting the ON
     * DELETE or ON UPDATE clause. SET DEFAULT
     *
     * @phpstan-param array{
     *   alias: string,
     *   foreign: string,
     *   local: string,
     *   class: class-string<Record>,
     *   type: int,
     *   table: Table,
     *   localTable: Table,
     *   name?: ?string,
     *   refTable?: ?Table,
     *   onDelete?: ?string,
     *   onUpdate?: ?string,
     *   deferred?: ?bool,
     *   deferrable?: ?bool,
     *   constraint?: ?bool,
     *   equal?: bool,
     *   cascade?: string[],
     *   owningSide?: bool,
     *   refClassRelationAlias?: ?string,
     *   foreignKeyName?: ?string,
     *   orderBy?: null|string|string[],
     * } $definition
     */
    public function __construct(array $definition)
    {
        foreach (['alias', 'foreign', 'local', 'class', 'type', 'table', 'localTable'] as $req) {
            if (!array_key_exists($req, $definition)) {
                throw new Exception("$req is required!");
            }
        }

        $this->definition = [
            'alias'                 => $definition['alias'],
            'foreign'               => $definition['foreign'],
            'local'                 => $definition['local'],
            'class'                 => $definition['class'],
            'type'                  => $definition['type'],
            'table'                 => $definition['table'],
            'localTable'            => $definition['localTable'],
            'name'                  => null,
            'refTable'              => null,
            'onDelete'              => null,
            'onUpdate'              => null,
            'deferred'              => null,
            'deferrable'            => null,
            'constraint'            => null,
            'equal'                 => false,
            'cascade'               => [],
            'owningSide'            => false,
            'refClassRelationAlias' => null,
            'foreignKeyName'        => null,
            'orderBy'               => null,
        ];

        foreach ($definition as $key => $value) {
            if (array_key_exists($key, $this->definition)) {
                /** @phpstan-ignore-next-line */
                $this->definition[$key] = $value;
            }
        }
    }

    /**
     * whether or not this relation has an explicit constraint
     */
    public function hasConstraint(): bool
    {
        return ($this->definition['constraint'] ||
                ($this->definition['onUpdate']) ||
                ($this->definition['onDelete']));
    }

    public function isDeferred(): ?bool
    {
        return $this->definition['deferred'];
    }

    public function isDeferrable(): ?bool
    {
        return $this->definition['deferrable'];
    }

    public function isEqual(): bool
    {
        return $this->definition['equal'];
    }

    /**
     * @param  mixed $offset
     */
    public function offsetExists($offset): bool
    {
        return isset($this->definition[$offset]);
    }

    /**
     * @param  mixed $offset
     * @return mixed
     */
    public function offsetGet($offset): mixed
    {
        if (isset($this->definition[$offset])) {
            return $this->definition[$offset];
        }

        return null;
    }

    /**
     * @param  mixed $offset
     * @param  mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        if (isset($this->definition[$offset])) {
            $this->definition[$offset] = $value;
        }
    }

    /**
     * @param  mixed $offset
     */
    public function offsetUnset($offset): void
    {
        /** @phpstan-ignore-next-line */
        $this->definition[$offset] = false;
    }

    /** @phpstan-return RelationDefinition */
    public function toArray(): array
    {
        return $this->definition;
    }

    /**
     * returns the relation alias
     */
    final public function getAlias(): string
    {
        return $this->definition['alias'];
    }

    /**
     * returns the relation type, either 0 or 1
     *
     * @see    Relation MANY_* and ONE_* constants
     */
    final public function getType(): int
    {
        return $this->definition['type'];
    }

    /**
     * Checks whether this relation cascades deletions to the related objects
     * on the application level.
     *
     * @return boolean
     */
    public function isCascadeDelete(): bool
    {
        return in_array('delete', $this->definition['cascade']);
    }

    /**
     * returns the foreign table object
     */
    final public function getTable(): Table
    {
        return Manager::getInstance()
            ->getConnectionForComponent($this->definition['class'])
            ->getTable($this->definition['class']);
    }

    /**
     * returns the name of the related class
     *
     * @phpstan-return class-string<Record>
     */
    final public function getClass(): string
    {
        return $this->definition['class'];
    }

    /**
     * returns the name of the local column
     */
    final public function getLocal(): string
    {
        return $this->definition['local'];
    }

    final public function getLocalTableName(): string
    {
        return $this->definition['localTable']->getTableName();
    }

    /**
     * returns the field name of the local column
     */
    final public function getLocalFieldName(): string
    {
        return $this->definition['localTable']->getFieldName($this->definition['local']);
    }

    /**
     * returns the column name of the local column
     */
    final public function getLocalColumnName(): string
    {
        return $this->definition['localTable']->getColumnName($this->definition['local']);
    }

    /**
     * returns the name of the foreignkey column where
     * the localkey column is pointing at
     */
    final public function getForeign(): string
    {
        return $this->definition['foreign'];
    }

    final public function getForeignTableName(): string
    {
        return $this->definition['table']->getTableName();
    }

    /**
     * returns the field name of the foreign column
     */
    final public function getForeignFieldName(): string
    {
        return $this->definition['table']->getFieldName($this->definition['foreign']);
    }

    /**
     * returns the column name of the foreign column
     */
    final public function getForeignColumnName(): string
    {
        return $this->definition['table']->getColumnName($this->definition['foreign']);
    }

    /**
     * returns whether or not this relation is a one-to-one relation
     */
    final public function isOneToOne(): bool
    {
        return ($this->definition['type'] == Relation::ONE);
    }

    public function getRelationDql(int $count): string
    {
        $component = $this->getTable()->getComponentName();
        $inParams = implode(', ', array_fill(0, $count, '?'));
        return "FROM $component WHERE $component.{$this->definition['foreign']} IN ($inParams){$this->getOrderBy($component)}";
    }

    /**
     * fetches a component related to given record
     *
     * @return Record|Collection|null
     */
    abstract public function fetchRelatedFor(Record $record);

    /**
     * Get the name of the foreign key for this relationship
     */
    public function getForeignKeyName(): string
    {
        if (isset($this->definition['foreignKeyName'])) {
            return $this->definition['foreignKeyName'];
        }
        return $this->definition['localTable']->getConnection()->generateUniqueRelationForeignKeyName($this);
    }

    /**
     * Get the relationship orderby SQL/DQL
     *
     * @param  string  $alias       The alias to use
     * @param  boolean $columnNames Whether or not to use column names instead of field names
     */
    public function getOrderBy($alias = null, $columnNames = false): ?string
    {
        if (!$alias) {
            $alias = $this->getTable()->getComponentName();
        }

        if ($orderBy = $this->getOrderByStatement($alias, $columnNames)) {
            return " ORDER BY $orderBy";
        }

        return null;
    }

    /**
     * Get the relationship orderby statement
     *
     * @param  string  $alias       The alias to use
     * @param  boolean $columnNames Whether or not to use column names instead of field names
     */
    public function getOrderByStatement($alias = null, $columnNames = false): ?string
    {
        $table = $this->getTable();

        if (!$alias) {
            $alias = $table->getComponentName();
        }

        if (isset($this->definition['orderBy'])) {
            return $table->processOrderBy($alias, $this->definition['orderBy'], $columnNames);
        } else {
            return $table->getOrderByStatement($alias, $columnNames);
        }
    }

    public function isRefClass(): bool
    {
        if ($this->isRefClass === null) {
            $this->isRefClass = false;
            $table             = $this->getTable();
            foreach ($table->getRelations() as $name => $relation) {
                foreach ($relation['table']->getRelations() as $relation) {
                    if (isset($relation['refTable']) && $relation['refTable'] === $table) {
                        $this->isRefClass = true;
                        break(2);
                    }
                }
            }
        }

        return $this->isRefClass;
    }
}
