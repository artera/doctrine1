<?php

class Doctrine_Relation_ForeignKey extends Doctrine_Relation
{
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
        $id         = [];
        $localTable = $record->getTable();
        foreach ((array) $this->definition['local'] as $local) {
            $value = $record->get($localTable->getFieldName($local));
            if (isset($value)) {
                $id[] = $value;
            }
        }
        if ($this->isOneToOne()) {
            if (!$record->exists() || empty($id)
                || !$this->definition['table']->getAttribute(Doctrine_Core::ATTR_LOAD_REFERENCES)
            ) {
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
            if (!$record->exists() || empty($id)
                || !$this->definition['table']->getAttribute(Doctrine_Core::ATTR_LOAD_REFERENCES)
            ) {
                $related = Doctrine_Collection::create($this->getTable());
            } else {
                $query   = $this->getRelationDql(1);
                $related = $this->getTable()->getConnection()->query($query, $id);
            }
            $related->setReference($record, $this);
        }
        return $related;
    }

    /**
     * getCondition
     *
     * @param string $alias
     *
     * @return string
     */
    public function getCondition($alias = null)
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
