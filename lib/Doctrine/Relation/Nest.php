<?php

class Doctrine_Relation_Nest extends Doctrine_Relation_Association
{
    /**
     * @return Doctrine_Collection
     */
    public function fetchRelatedFor(Doctrine_Record $record)
    {
        $id = $record->getIncremented();

        if (empty($id) || !$this->definition['table']->getAttribute(Doctrine_Core::ATTR_LOAD_REFERENCES)) {
            return Doctrine_Collection::create($this->getTable());
        } else {
            $q         = new Doctrine_RawSql($this->getTable()->getConnection());
            $formatter = $q->getConnection()->formatter;

            $assocTable            = $this->getAssociationFactory()->getTableName();
            $tableName             = $record->getTable()->getTableName();
            $identifierColumnNames = $record->getTable()->getIdentifierColumnNames();
            $identifier = array_pop($identifierColumnNames);
            assert($identifier !== null);
            $identifier            = $formatter->quoteIdentifier($identifier);

            $sub = 'SELECT ' . $formatter->quoteIdentifier($this->getForeignRefColumnName())
                 . ' FROM ' . $formatter->quoteIdentifier($assocTable)
                 . ' WHERE ' . $formatter->quoteIdentifier($this->getLocalRefColumnName())
                 . ' = ?';

            $condition[]     = $formatter->quoteIdentifier($tableName) . '.' . $identifier . ' IN (' . $sub . ')';
            $joinCondition[] = $formatter->quoteIdentifier($tableName) . '.' . $identifier . ' = ' . $formatter->quoteIdentifier($assocTable) . '.' . $formatter->quoteIdentifier($this->getForeignRefColumnName());

            if ($this->definition['equal']) {
                $sub2 = 'SELECT ' . $formatter->quoteIdentifier($this->getLocalRefColumnName())
                        . ' FROM ' . $formatter->quoteIdentifier($assocTable)
                        . ' WHERE ' . $formatter->quoteIdentifier($this->getForeignRefColumnName())
                        . ' = ?';

                $condition[]     = $formatter->quoteIdentifier($tableName) . '.' . $identifier . ' IN (' . $sub2 . ')';
                $joinCondition[] = $formatter->quoteIdentifier($tableName) . '.' . $identifier . ' = ' . $formatter->quoteIdentifier($assocTable) . '.' . $formatter->quoteIdentifier($this->getLocalRefColumnName());
            }
            $q->select('{' . $tableName . '.*}, {' . $assocTable . '.*}')
                ->from($formatter->quoteIdentifier($tableName) . ' INNER JOIN ' . $formatter->quoteIdentifier($assocTable) . ' ON ' . implode(' OR ', $joinCondition))
                ->where(implode(' OR ', $condition));
            if ($orderBy = $this->getOrderByStatement($tableName, true)) {
                $q->addOrderBy($orderBy);
            } else {
                $q->addOrderBy($formatter->quoteIdentifier($tableName) . '.' . $identifier . ' ASC');
            }
            $q->addComponent($tableName, $this->getClass());

            $path = $this->getClass() . '.' . $this->getAssociationFactory()->getComponentName();
            if ($this->definition['refClassRelationAlias']) {
                $path = $this->getClass() . '.' . $this->definition['refClassRelationAlias'];
            }
            $q->addComponent($assocTable, $path);

            $params = ($this->definition['equal']) ? [$id, $id] : [$id];

            /** @var Doctrine_Collection $res No hydration parameter passed */
            $res = $q->execute($params);

            return $res;
        }
    }
}
