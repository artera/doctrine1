<?php

class Doctrine_Relation_Association extends Doctrine_Relation
{
    /**
     * @var array $definition @see __construct()
     * @phpstan-var array{
     *   alias: string,
     *   foreign: string,
     *   local: string,
     *   class: class-string<Doctrine_Record>,
     *   type: int,
     *   table: Doctrine_Table,
     *   localTable: Doctrine_Table,
     *   name: ?string,
     *   refTable: Doctrine_Table,
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
    protected array $definition;

    /**
     * @phpstan-param array{
     *   alias: string,
     *   foreign: string,
     *   local: string,
     *   class: class-string<Doctrine_Record>,
     *   type: int,
     *   table: Doctrine_Table,
     *   localTable: Doctrine_Table,
     *   name?: ?string,
     *   refTable: Doctrine_Table,
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
        if (!array_key_exists('refTable', $definition)) {
            throw new Doctrine_Exception("refTable is required!");
        }
        parent::__construct($definition);
    }

    /**
     * @return Doctrine_Table
     */
    public function getAssociationFactory()
    {
        return $this->definition['refTable'];
    }

    /**
     * @return Doctrine_Table
     */
    public function getAssociationTable()
    {
        return $this->definition['refTable'];
    }

    public function getRelationDql(int $count, string $context = 'record'): string
    {
        $table     = $this->definition['refTable'];
        $component = $this->definition['refTable']->getComponentName();
        $dql       = '';

        switch ($context) {
            case 'record':
                $sub = substr(str_repeat('?, ', $count), 0, -2);
                $dql = 'FROM ' . $this->getTable()->getComponentName();
                $dql .= '.' . $component;
                $dql .= ' WHERE ' . $this->getTable()->getComponentName()
                . '.' . $component . '.' . $this->getLocalRefColumnName() . ' IN (' . $sub . ')';
                $dql .= $this->getOrderBy($this->getTable()->getComponentName(), false);
                break;
            case 'collection':
                $sub = substr(str_repeat('?, ', $count), 0, -2);
                $dql = 'FROM ' . $component . '.' . $this->getTable()->getComponentName();
                $dql .= ' WHERE ' . $component . '.' . $this->getLocalRefColumnName() . ' IN (' . $sub . ')';
                $dql .= $this->getOrderBy($component, false);
                break;
        }

        return $dql;
    }

    /**
     * getLocalRefColumnName
     * returns the column name of the local reference column
     *
     * @return string
     */
    final public function getLocalRefColumnName()
    {
        return $this->definition['refTable']->getColumnName($this->definition['local']);
    }

    /**
     * getLocalRefFieldName
     * returns the field name of the local reference column
     *
     * @return string
     */
    final public function getLocalRefFieldName()
    {
        return $this->definition['refTable']->getFieldName($this->definition['local']);
    }

    /**
     * getForeignRefColumnName
     * returns the column name of the foreign reference column
     *
     * @return string
     */
    final public function getForeignRefColumnName()
    {
        return $this->definition['refTable']->getColumnName($this->definition['foreign']);
    }

    /**
     * getForeignRefFieldName
     * returns the field name of the foreign reference column
     *
     * @return string
     */
    final public function getForeignRefFieldName()
    {
        return $this->definition['refTable']->getFieldName($this->definition['foreign']);
    }

    /**
     * fetchRelatedFor
     *
     * fetches a component related to given record
     *
     * @param  Doctrine_Record $record
     * @return Doctrine_Record|Doctrine_Collection
     */
    public function fetchRelatedFor(Doctrine_Record $record)
    {
        $id = $record->getIncremented();
        if (empty($id) || !$this->definition['table']->getAttribute(Doctrine_Core::ATTR_LOAD_REFERENCES)) {
            $coll = Doctrine_Collection::create($this->getTable());
        } else {
            $coll = $this->getTable()->getConnection()->query($this->getRelationDql(1), [$id]);
        }
        $coll->setReference($record, $this);
        return $coll;
    }
}
