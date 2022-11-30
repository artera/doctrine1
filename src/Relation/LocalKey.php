<?php

namespace Doctrine1\Relation;

class LocalKey extends \Doctrine1\Relation
{
    /**
     * fetches a component related to given record
     *
     * @return \Doctrine1\Record|null
     */
    public function fetchRelatedFor(\Doctrine1\Record $record)
    {
        $localFieldName = $record->getTable()->getFieldName($this->definition['local']);
        $id = $record->get($localFieldName);
        $related = null;
        $loadReferences = $this->definition['table']->getAttribute(\Doctrine1\Core::ATTR_LOAD_REFERENCES);

        if ($loadReferences && $id !== null) {
            $dql = 'FROM ' . $this->getTable()->getComponentName()
                 . ' WHERE ' . $this->getCondition() . $this->getOrderBy(null, false);

            $related = $this->getTable()
                ->getConnection()
                ->query($dql, [$id])
                ->getFirst();
        }

        if ($related === null) {
            $column = $record->getTable()->getColumnDefinition($this->definition['local']);
            if (!empty($column['notnull']) || !empty($column['primary'])) {
                $related = $this->getTable()->create();
            } else {
                return null;
            }
        }

        if (!$loadReferences && $id !== null) {
            $related->assignIdentifier($id);
            $related->state(\Doctrine1\Record\State::PROXY);
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
