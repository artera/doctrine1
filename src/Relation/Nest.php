<?php

namespace Doctrine1\Relation;

class Nest extends \Doctrine1\Relation\Association
{
    /**
     * @return \Doctrine1\Collection
     */
    public function fetchRelatedFor(\Doctrine1\Record $record)
    {
        $id = $record->getIncremented();

        if (empty($id) || !$this->definition['table']->getLoadReferences()) {
            return \Doctrine1\Collection::create($this->getTable());
        }

        $q = new \Doctrine1\RawSql($this->getTable()->getConnection());
        $formatter = $q->getConnection()->formatter;

        $assocTable = $this->getAssociationFactory()->getTableName();
        $tableName = $record->getTable()->getTableName();
        $identifierColumnNames = $record->getTable()->getIdentifierColumnNames();
        $identifier = array_pop($identifierColumnNames);
        assert($identifier !== null);
        $identifier = $formatter->quoteIdentifier($identifier);

        $qForeignColName = $formatter->quoteIdentifier($this->getForeignRefColumnName());
        $qLocalColName = $formatter->quoteIdentifier($this->getLocalRefColumnName());
        $qTableName = $formatter->quoteIdentifier($tableName);
        $qAssocTable = $formatter->quoteIdentifier($assocTable);

        $condition[] = "$qTableName.$identifier IN (SELECT $qForeignColName FROM $qAssocTable WHERE $qLocalColName = ?)";
        $joinCondition[] = "$qTableName.$identifier = $qAssocTable.$qForeignColName";

        if ($this->definition['equal']) {
            $condition[] = "$qTableName.$identifier IN (SELECT $qLocalColName FROM $qAssocTable WHERE $qForeignColName = ?)";
            $joinCondition[] = "$qTableName.$identifier = $qAssocTable.$qLocalColName";
        }
        $q->select("\{$tableName.*}, \{$assocTable.*}")
            ->from("$qTableName INNER JOIN $qAssocTable ON " . implode(' OR ', $joinCondition))
            ->where(implode(' OR ', $condition));
        if ($orderBy = $this->getOrderByStatement($tableName, true)) {
            $q->addOrderBy($orderBy);
        } else {
            $q->addOrderBy("$qTableName.$identifier ASC");
        }
        $q->addComponent($tableName, $this->getClass());

        $path = $this->getClass() . '.' . ($this->definition['refClassRelationAlias'] ?: $this->getAssociationFactory()->getComponentName());
        $q->addComponent($assocTable, $path);

        $params = $this->definition['equal'] ? [$id, $id] : [$id];

        /** @var \Doctrine1\Collection $res No hydration parameter passed */
        $res = $q->execute($params);

        return $res;
    }
}
