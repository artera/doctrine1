<?php

namespace Doctrine1\Relation;

class ForeignKey extends \Doctrine1\Relation
{
    /**
     * fetches a component related to given record
     *
     * @return \Doctrine1\Record|\Doctrine1\Collection
     */
    public function fetchRelatedFor(\Doctrine1\Record $record)
    {
        $id         = [];
        $localTable = $record->getTable();
        foreach ((array) $this->definition['local'] as $local) {
            $value = $record->get($localTable->getFieldName($local));
            if (isset($value)) {
                $id[] = $value;
            }
        }
        if ($this->isOneToOne()) {
            if (!$record->exists() || empty($id) || !$this->definition['table']->getLoadReferences()) {
                $related = $this->getTable()->create();
            } else {
                $dql = 'FROM ' . $this->getTable()->getComponentName()
                      . ' WHERE ' . $this->getCondition() . $this->getOrderBy(null, false);

                $coll    = $this->getTable()->getConnection()->query($dql, $id);
                $related = $coll[0];
            }

            $related->set(
                $related->getTable()->getFieldName($this->definition['foreign']),
                $record,
                false
            );
        } else {
            if (!$record->exists() || empty($id) || !$this->definition['table']->getLoadReferences()) {
                $related = \Doctrine1\Collection::create($this->getTable());
            } else {
                $query   = $this->getRelationDql(1);
                $related = $this->getTable()->getConnection()->query($query, $id);
            }
            $related->setReference($record, $this);
        }
        return $related;
    }

    public function getCondition(?string $alias = null): string
    {
        if (!$alias) {
            $alias = $this->getTable()->getComponentName();
        }
        $conditions = [];
        foreach ((array) $this->definition['foreign'] as $foreign) {
            $conditions[] = $alias . '.' . $foreign . ' = ?';
        }
        return implode(' AND ', $conditions);
    }
}
