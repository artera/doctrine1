<?php

class Doctrine_Relation_Association_Self extends Doctrine_Relation_Association
{
    public function getRelationDql(int $count, string $context = 'record'): string
    {
        if ($context !== 'record' && $context !== 'collection') {
            return '';
        }

        $table = $this->definition['table'];
        $refTable = $this->definition['refTable'];

        $t = $table->getComponentName();
        $r = $refTable->getComponentName();
        $local = $this->definition['local'];

        if ($context === 'record') {
            $identifierColumnNames = $table->getIdentifierColumnNames();
            $id = array_pop($identifierColumnNames);

            $sub1 = "SELECT {$this->definition['foreign']}
                     FROM $r
                     WHERE $local = ?";

            $sub2 = "SELECT $local
                     FROM $r
                     WHERE {$this->definition['foreign']} = ?";

            $order = $this->getOrderBy($t, false);

            return "FROM $t.$r WHERE $t.$id IN ($sub1) || $t.$id IN ($sub2)";
        }

        $sub = substr(str_repeat('?, ', $count), 0, -2);
        $order = $this->getOrderBy($r, false);

        return "FROM $r.$t WHERE $r.$local IN ($sub)";
    }

    /**
     * @return Doctrine_Collection|array|int
     *
     * @phpstan-return array|bool|Doctrine_Collection<Doctrine_Record>|Doctrine_Collection_OnDemand<Doctrine_Record>|float|int|string
     */
    public function fetchRelatedFor(Doctrine_Record $record): array|bool|Doctrine_Collection|Doctrine_Collection_OnDemand|float|int|string
    {
        $id = $record->getIncremented();

        $q = new Doctrine_RawSql();

        $assocTable            = $this->getAssociationFactory()->getTableName();
        $tableName             = $record->getTable()->getTableName();
        $identifierColumnNames = $record->getTable()->getIdentifierColumnNames();
        $identifier            = array_pop($identifierColumnNames);

        $sub = 'SELECT ' . $this->getForeign() .
                   ' FROM ' . $assocTable .
                   ' WHERE ' . $this->getLocal() .
                   ' = ?';

        $sub2 = 'SELECT ' . $this->getLocal() .
                  ' FROM ' . $assocTable .
                  ' WHERE ' . $this->getForeign() .
                  ' = ?';

        $q->select('{' . $tableName . '.*}, {' . $assocTable . '.*}')
            ->from(
                $tableName . ' INNER JOIN ' . $assocTable . ' ON ' .
                 $tableName . '.' . $identifier . ' = ' . $assocTable . '.' . $this->getLocal() . ' OR ' .
                 $tableName . '.' . $identifier . ' = ' . $assocTable . '.' . $this->getForeign()
            )
            ->where(
                $tableName . '.' . $identifier . ' IN (' . $sub . ') OR ' .
                  $tableName . '.' . $identifier . ' IN (' . $sub2 . ')'
            );
        $q->addComponent($tableName, $record->getTable()->getComponentName());
        $q->addComponent($assocTable, $record->getTable()->getComponentName() . '.' . $this->getAssociationFactory()->getComponentName());
        $q->orderBy((string) $this->getOrderByStatement($tableName, true));

        return $q->execute([$id, $id]);
    }
}
