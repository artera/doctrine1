<?php

namespace Doctrine1\Relation;

class SelfAssociation extends \Doctrine1\Relation\Association
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
     * @return \Doctrine1\Collection|array|int
     *
     * @phpstan-return array|bool|\Doctrine1\Collection<\Doctrine1\Record>|\Doctrine1\Collection\OnDemand<\Doctrine1\Record>|float|int|string
     */
    public function fetchRelatedFor(\Doctrine1\Record $record): array|bool|\Doctrine1\Collection|\Doctrine1\Collection\OnDemand|float|int|string
    {
        $id = $record->getIncremented();

        $q = new \Doctrine1\RawSql();

        $assocTable            = $this->getAssociationFactory()->getTableName();
        $tableName             = $record->getTable()->getTableName();
        $identifierColumnNames = $record->getTable()->getIdentifierColumnNames();
        $identifier            = array_pop($identifierColumnNames);
        $foreign = $this->getForeign();
        $local = $this->getLocal();

        $sub = "SELECT $foreign FROM $assocTable WHERE $local = ?";
        $sub2 = "SELECT $local FROM $assocTable WHERE $foreign = ?";

        $q->select('{' . $tableName . '.*}, {' . $assocTable . '.*}')
            ->from("$tableName INNER JOIN $assocTable ON $tableName.$identifier = $assocTable.$local OR $tableName.$identifier = $assocTable.$foreign")
            ->where("$tableName.$identifier IN ($sub) OR $tableName.$identifier IN ($sub2)");
        $q->addComponent($tableName, $record->getTable()->getComponentName());
        $q->addComponent($assocTable, $record->getTable()->getComponentName() . '.' . $this->getAssociationFactory()->getComponentName());
        $q->orderBy((string) $this->getOrderByStatement($tableName, true));

        return $q->execute([$id, $id]);
    }
}
