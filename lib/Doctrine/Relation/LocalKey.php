<?php

class Doctrine_Relation_LocalKey extends Doctrine_Relation
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
        $localFieldName = $record->getTable()->getFieldName($this->definition['local']);
        $id             = $record->get($localFieldName);

        if (is_null($id) || !$this->definition['table']->getAttribute(Doctrine_Core::ATTR_LOAD_REFERENCES)) {
            $related = $this->getTable()->create();

            // Ticket #1131 Patch.
            if (!is_null($id)) {
                $related->assignIdentifier($id);
                $related->state(Doctrine_Record_State::PROXY());
            }
        } else {
            $dql = 'FROM ' . $this->getTable()->getComponentName()
                 . ' WHERE ' . $this->getCondition() . $this->getOrderBy(null, false);

            $related = $this->getTable()
                ->getConnection()
                ->query($dql, [$id])
                ->getFirst();

            if (!$related || empty($related)) {
                $related = $this->getTable()->create();
            }
        }

        $record->set($localFieldName, $id, false);

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
        return $alias . '.' . $this->definition['foreign'] . ' = ?';
    }
}
