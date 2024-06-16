<?php

namespace Doctrine1\Connection;

use Doctrine1\Collection;
use Doctrine1\IdentifierType;
use Doctrine1\None;
use PDOException;

/**
 * @template Connection of \Doctrine1\Connection
 * @extends \Doctrine1\Connection\Module<Connection>
 */
class UnitOfWork extends \Doctrine1\Connection\Module
{
    /**
     * Saves the given record and all associated records.
     * (The save() operation is always cascaded in 0.10/1.0).
     */
    public function saveGraph(\Doctrine1\Record $record, bool $replace = false): bool
    {
        $record->assignInheritanceValues();

        $conn = $this->getConnection();
        $conn->connect();

        $state = $record->state();
        if ($state->isLocked()) {
            return false;
        }

        $savepoint = $conn->beginInternalTransaction();

        try {
            $event = $record->invokeSaveHooks("pre", "save");
            $isValid = true;

            try {
                $this->saveRelatedLocalKeys($record);
            } catch (\Doctrine1\Validator\Exception $e) {
                $isValid = false;
                $savepoint->exception ??= $e;
                foreach ($e->getInvalidRecords() as $invalid) {
                    $savepoint->addInvalid($invalid);
                }
            }

            if ($state->isTransient()) {
                if ($replace) {
                    $isValid = $this->replace($record);
                } else {
                    $isValid = $this->insert($record);
                }
            } elseif (!$state->isClean()) {
                if ($replace) {
                    $isValid = $this->replace($record);
                } else {
                    $isValid = $this->update($record);
                }
            }

            $aliasesUnlinkInDb = [];

            if ($isValid) {
                // NOTE: what about referential integrity issues?
                foreach ($record->getPendingDeletes() as $pendingDelete) {
                    $pendingDelete->delete();
                }

                foreach ($record->getPendingUnlinks() as $alias => $ids) {
                    if ($ids === false) {
                        $record->unlinkInDb($alias, []);
                        $aliasesUnlinkInDb[] = $alias;
                    } elseif ($ids) {
                        $record->unlinkInDb($alias, array_keys($ids));
                        $aliasesUnlinkInDb[] = $alias;
                    }
                }
                $record->resetPendingUnlinks();

                $record->invokeSaveHooks("post", "save", $event);
            } else {
                $savepoint->addInvalid($record);
            }

            if ($isValid) {
                $state = $record->state();
                $record->state($state->lock());
                try {
                    $saveLater = $this->saveRelatedForeignKeys($record);
                    foreach ($saveLater as $fk) {
                        $alias = $fk->getAlias();

                        if ($record->hasReference($alias)) {
                            $obj = $record->$alias;

                            // check that the related object is not an instance of \Doctrine1\None
                            if ($obj && !($obj instanceof \Doctrine1\None)) {
                                $processDiff = !in_array($alias, $aliasesUnlinkInDb);
                                $obj->save($conn, $processDiff);
                            }
                        }
                    }

                    // save the MANY-TO-MANY associations
                    $this->saveAssociations($record);
                } finally {
                    $record->state($state);
                }
            }
        } catch (\Throwable $e) {
            // Make sure we roll back our internal transaction
            //$record->state($state);
            $savepoint->rollback();
            throw $e;
        }

        $savepoint->commit();
        $record->clearInvokedSaveHooks();

        return true;
    }

    /**
     * Deletes the given record and all the related records that participate
     * in an application-level delete cascade.
     *
     * this event can be listened by the onPreDelete and onDelete listeners
     */
    public function delete(\Doctrine1\Record $record): void
    {
        $deletions = [];
        $this->collectDeletions($record, $deletions);
        $this->executeDeletions($deletions);
    }

    /**
     * Collects all records that need to be deleted by applying defined
     * application-level delete cascades.
     *
     * @param array $deletions Map of the records to delete. Keys=Oids Values=Records.
     */
    private function collectDeletions(\Doctrine1\Record $record, array &$deletions): void
    {
        if (!$record->exists()) {
            return;
        }

        $deletions[$record->getOid()] = $record;
        $this->cascadeDelete($record, $deletions);
    }

    /**
     * Executes the deletions for all collected records during a delete operation
     * (usually triggered through $record->delete()).
     *
     * @param  array $deletions Map of the records to delete. Keys=Oids Values=Records.
     */
    private function executeDeletions(array $deletions): void
    {
        // collect class names
        $classNames = [];
        foreach ($deletions as $record) {
            $classNames[] = $record->getTable()->getComponentName();
        }
        $classNames = array_unique($classNames);

        // order deletes
        $executionOrder = $this->buildFlushTree($classNames);

        // execute
        $savepoint = $this->conn->beginInternalTransaction();

        try {
            for ($i = count($executionOrder) - 1; $i >= 0; $i--) {
                $className = $executionOrder[$i];
                $table = $this->conn->getTable($className);

                // collect identifiers
                $identifierMaps = [];
                $deletedRecords = [];
                foreach ($deletions as $oid => $record) {
                    if ($record->getTable()->getComponentName() == $className) {
                        $this->preDelete($record);
                        $identifierMaps[] = $record->identifier();
                        $deletedRecords[] = $record;
                        unset($deletions[$oid]);
                    }
                }

                if (count($deletedRecords) < 1) {
                    continue;
                }

                // extract query parameters (only the identifier values are of interest)
                $params = [];
                $columnNames = [];
                foreach ($identifierMaps as $idMap) {
                    foreach ($idMap as $fieldName => $value) {
                        $params[] = $value;
                        $columnNames[] = $table->getColumnName($fieldName);
                    }
                }
                $columnNames = array_unique($columnNames);

                // delete
                $tableName = $table->getTableName();
                $sql = "DELETE FROM " . $this->conn->quoteIdentifier($tableName) . " WHERE ";

                if ($table->isIdentifierComposite()) {
                    $sql .= $this->buildSqlCompositeKeyCondition($columnNames, count($identifierMaps));
                    $this->conn->exec($sql, $params);
                } else {
                    $sql .= $this->buildSqlSingleKeyCondition($columnNames[0], count($params));
                    $this->conn->exec($sql, $params);
                }

                // adjust state, remove from identity map and inform postDelete listeners
                foreach ($deletedRecords as $record) {
                    $record->state(\Doctrine1\Record\State::TCLEAN);
                    $record->getTable()->removeRecord($record);
                    $this->postDelete($record);
                }
            }

            // trigger postDelete for records skipped during the deletion (veto!)
            foreach ($deletions as $skippedRecord) {
                $this->postDelete($skippedRecord);
            }
        } catch (\Throwable $e) {
            $savepoint->rollback();
            throw $e;
        }

        $savepoint->commit();
    }

    /**
     * Builds the SQL condition to target multiple records who have a single-column
     * primary key.
     *
     * @param  integer $numRecords  The number of records that are going to be deleted.
     * @return string  The SQL condition "pk = ? OR pk = ? OR pk = ? ..."
     */
    private function buildSqlSingleKeyCondition(string $columnName, int $numRecords): string
    {
        $idColumn = $this->conn->quoteIdentifier($columnName);
        return implode(" OR ", array_fill(0, $numRecords, "$idColumn = ?"));
    }

    /**
     * Builds the SQL condition to target multiple records who have a composite primary key.
     *
     * @phpstan-param list<string> $columnNames
     * @param  integer $numRecords  The number of records that are going to be deleted.
     * @return string  The SQL condition "(pk1 = ? AND pk2 = ?) OR (pk1 = ? AND pk2 = ?) ..."
     */
    private function buildSqlCompositeKeyCondition(array $columnNames, int $numRecords): string
    {
        $singleCondition = "";
        foreach ($columnNames as $columnName) {
            $columnName = $this->conn->quoteIdentifier($columnName);
            if ($singleCondition === "") {
                $singleCondition .= "($columnName = ?";
            } else {
                $singleCondition .= " AND $columnName = ?";
            }
        }
        $singleCondition .= ")";
        $fullCondition = implode(" OR ", array_fill(0, $numRecords, $singleCondition));

        return $fullCondition;
    }

    /**
     * Cascades an ongoing delete operation to related objects. Applies only on relations
     * that have 'delete' in their cascade options.
     * This is an application-level cascade. Related objects that participate in the
     * cascade and are not yet loaded are fetched from the database.
     * Exception: many-valued relations are always (re-)fetched from the database to
     * make sure we have all of them.
     *
     * @param  \Doctrine1\Record $record The record for which the delete operation will be cascaded.
     * @throws PDOException    If something went wrong at database level
     */
    protected function cascadeDelete(\Doctrine1\Record $record, array &$deletions): void
    {
        foreach ($record->getTable()->getRelations() as $relation) {
            if ($relation->isCascadeDelete()) {
                $fieldName = $relation->getAlias();
                // if it's a xToOne relation and the related object is already loaded
                // we don't need to refresh.
                if (!($relation->getType() == \Doctrine1\Relation::ONE && isset($record->$fieldName))) {
                    $record->refreshRelated($relation->getAlias());
                }
                $relatedObjects = $record->get($relation->getAlias());
                if ($relatedObjects instanceof \Doctrine1\Record && $relatedObjects->exists() && !isset($deletions[$relatedObjects->getOid()])) {
                    $this->collectDeletions($relatedObjects, $deletions);
                } elseif ($relatedObjects instanceof \Doctrine1\Collection && count($relatedObjects) > 0) {
                    // cascade the delete to the other objects
                    foreach ($relatedObjects as $object) {
                        if (!isset($deletions[$object->getOid()])) {
                            $this->collectDeletions($object, $deletions);
                        }
                    }
                }
            }
        }
    }

    /**
     * saves all related (through ForeignKey) records to $record
     *
     * @throws PDOException         if something went wrong at database level
     * @return \Doctrine1\Relation\ForeignKey[]
     */
    public function saveRelatedForeignKeys(\Doctrine1\Record $record): array
    {
        $saveLater = [];
        foreach ($record->getReferences() as $k => $v) {
            $rel = $record->getTable()->getRelation($k);
            if ($rel instanceof \Doctrine1\Relation\ForeignKey) {
                $saveLater[$k] = $rel;
            }
        }

        return $saveLater;
    }

    /**
     * saves all related (through LocalKey) records to $record
     *
     * @throws PDOException         if something went wrong at database level
     * @throws \Doctrine1\Validator\Exception
     */
    public function saveRelatedLocalKeys(\Doctrine1\Record $record): void
    {
        $state = $record->state();
        $record->state($state->lock());

        try {
            foreach ($record->getReferences() as $k => $v) {
                $rel = $record->getTable()->getRelation($k);

                $local = $rel->getLocal();
                $foreign = $rel->getForeign();

                if (!$rel instanceof \Doctrine1\Relation\LocalKey) {
                    continue;
                }

                // ONE-TO-ONE relationship
                $obj = $record->get($rel->getAlias());

                // Protection against infinite function recursion before attempting to save
                if ($obj instanceof \Doctrine1\Record && $obj->isModified()) {
                    $obj->save($this->conn);

                    $id = array_values($obj->identifier());

                    if (count($id) === 1) {
                        $field = $record->getTable()->getFieldName($rel->getLocal());
                        $record->set($field, $id[0]);
                    }
                }
            }
        } finally {
            $record->state($state);
        }
    }

    /**
     * this method takes a diff of one-to-many / many-to-many original and
     * current collections and applies the changes
     *
     * for example if original many-to-many related collection has records with
     * primary keys 1,2 and 3 and the new collection has records with primary keys
     * 3, 4 and 5, this method would first destroy the associations to 1 and 2 and then
     * save new associations to 4 and 5
     *
     * @throws \Doctrine1\Connection\Exception         if something went wrong at database level
     * @param  \Doctrine1\Record $record
     */
    public function saveAssociations(\Doctrine1\Record $record): void
    {
        foreach ($record->getReferences() as $k => $v) {
            if ($v === null || $v instanceof None) {
                continue;
            }

            $rel = $record->getTable()->getRelation($k);

            if (!$rel instanceof \Doctrine1\Relation\Association) {
                continue;
            }

            assert($v instanceof Collection);

            if ($this->conn->getCascadeSaves() || $v->isModified()) {
                $v->save($this->conn, false);
            }

            $assocTable = $rel->getAssociationTable();
            foreach ($v->getDeleteDiff() as $r) {
                $query = "DELETE FROM {$assocTable->getTableName()} WHERE {$rel->getForeignRefColumnName()} = ? AND {$rel->getLocalRefColumnName()} = ?";
                $this->conn->execute($query, [$r->getIncremented(), $record->getIncremented()]);
            }

            foreach ($v->getInsertDiff() as $r) {
                $assocRecord = $assocTable->create();
                $assocRecord->set($assocTable->getFieldName($rel->getForeign()), $r);
                $assocRecord->set($assocTable->getFieldName($rel->getLocal()), $record);
                $this->saveGraph($assocRecord);
            }
            // take snapshot of collection state, so that we know when its modified again
            $v->takeSnapshot();
        }
    }

    /**
     * Invokes preDelete event listeners.
     */
    private function preDelete(\Doctrine1\Record $record): void
    {
        $event = new \Doctrine1\Event($record, \Doctrine1\Event::RECORD_DELETE);
        $record->preDelete($event);
        $record->getTable()->getRecordListener()->preDelete($event);
    }

    /**
     * Invokes postDelete event listeners.
     *
     * @return void
     */
    private function postDelete(\Doctrine1\Record $record): void
    {
        $event = new \Doctrine1\Event($record, \Doctrine1\Event::RECORD_DELETE);
        $record->postDelete($event);
        $record->getTable()->getRecordListener()->postDelete($event);
    }

    /**
     * persists all the pending records from all tables
     *
     * @throws PDOException         if something went wrong at database level
     */
    public function saveAll(): void
    {
        // get the flush tree
        $tree = $this->buildFlushTree($this->conn->getTables());

        // save all records
        foreach ($tree as $name) {
            $table = $this->conn->getTable($name);
            $repo = $table->getRepository();
            if ($repo === null) {
                continue;
            }
            foreach ($repo as $record) {
                $this->saveGraph($record);
            }
        }
    }

    /**
     * updates given record
     *
     * @return boolean                  whether or not the update was successful
     */
    public function update(\Doctrine1\Record $record): bool
    {
        $event = $record->invokeSaveHooks("pre", "update");

        if (!$record->isValid(false, false)) {
            return false;
        }

        $table = $record->getTable();
        $identifier = $record->identifier();
        $array = $record->getPrepared();
        $this->conn->update($table, $array, $identifier);
        $record->assignIdentifier(true);

        $record->invokeSaveHooks("post", "update", $event);

        return true;
    }

    /**
     * Inserts a record into database.
     *
     * This method inserts a transient record in the database, and adds it
     * to the identity map of its correspondent table. It proxies to @see
     * processSingleInsert(), trigger insert hooks and validation of data
     * if required.
     *
     * @return boolean                  false if record is not valid
     */
    public function insert(\Doctrine1\Record $record): bool
    {
        $event = $record->invokeSaveHooks("pre", "insert");

        if (!$record->isValid(false, false)) {
            return false;
        }

        $table = $record->getTable();
        $this->processSingleInsert($record);

        $table->addRecord($record);
        $record->invokeSaveHooks("post", "insert", $event);

        return true;
    }

    /**
     * Replaces a record into database.
     *
     * @return boolean                  false if record is not valid
     */
    public function replace(\Doctrine1\Record $record): bool
    {
        if ($record->exists()) {
            return $this->update($record);
        } elseif (!$record->isValid()) {
            return false;
        }

        $this->assignSequence($record);

        $saveEvent = $record->invokeSaveHooks("pre", "save");
        $insertEvent = $record->invokeSaveHooks("pre", "insert");

        $table = $record->getTable();
        $identifier = (array) $table->getIdentifier();
        $data = $record->getPrepared();

        foreach ($data as $key => $value) {
            if ($value instanceof \Doctrine1\Expression) {
                $data[$key] = $value->getSql();
            }
        }

        $result = $this->conn->replace($table, $data, $identifier);

        $record->invokeSaveHooks("post", "insert", $insertEvent);
        $record->invokeSaveHooks("post", "save", $saveEvent);

        $this->assignIdentifier($record);

        return true;
    }

    /**
     * Inserts a transient record in its table.
     *
     * This method inserts the data of a single record in its assigned table,
     * assigning to it the autoincrement primary key (if any is defined).
     */
    public function processSingleInsert(\Doctrine1\Record $record): void
    {
        $fields = $record->getPrepared();
        $table = $record->getTable();

        // Populate fields with a blank array so that a blank records can be inserted
        if (empty($fields)) {
            foreach ($table->getFieldNames() as $field) {
                $fields[$field] = null;
            }
        }

        $this->assignSequence($record, $fields);
        $this->conn->insert($table, $fields);
        $this->assignIdentifier($record);
    }

    /**
     * builds a flush tree that is used in transactions
     *
     * The returned array has all the initialized components in
     * 'correct' order. Basically this means that the records of those
     * components can be saved safely in the order specified by the returned array.
     *
     * @param  (\Doctrine1\Table|class-string<\Doctrine1\Record>)[] $tables an array of \Doctrine1\Table objects or component names
     * @return array            an array of component names in flushing order
     */
    public function buildFlushTree(array $tables): array
    {
        // determine classes to order. only necessary because the $tables param
        // can contain strings or table objects...
        $classesToOrder = [];
        foreach ($tables as $table) {
            if (!($table instanceof \Doctrine1\Table)) {
                $table = $this->conn->getTable($table);
            }
            $classesToOrder[] = $table->getComponentName();
        }
        $classesToOrder = array_unique($classesToOrder);

        if (count($classesToOrder) < 2) {
            return $classesToOrder;
        }

        // build the correct order
        $flushList = [];
        foreach ($classesToOrder as $class) {
            $table = $this->conn->getTable($class);
            $currentClass = $table->getComponentName();

            $index = array_search($currentClass, $flushList);

            if ($index === false) {
                $flushList[] = $currentClass;
                $index = max(array_keys($flushList));
            }

            $rels = $table->getRelations();

            // move all foreignkey relations to the beginning
            foreach ($rels as $key => $rel) {
                if ($rel instanceof \Doctrine1\Relation\ForeignKey) {
                    unset($rels[$key]);
                    array_unshift($rels, $rel);
                }
            }

            foreach ($rels as $rel) {
                $relatedClassName = $rel->getTable()->getComponentName();

                if (!in_array($relatedClassName, $classesToOrder)) {
                    continue;
                }

                $relatedCompIndex = array_search($relatedClassName, $flushList);
                $type = $rel->getType();

                // skip self-referenced relations
                if ($relatedClassName === $currentClass) {
                    continue;
                }

                if ($rel instanceof \Doctrine1\Relation\ForeignKey) {
                    // the related component needs to come after this component in
                    // the list (since it holds the fk)

                    if ($relatedCompIndex === false) {
                        $flushList[] = $relatedClassName;
                    } elseif ($relatedCompIndex >= $index) {
                        // it's already in the right place
                        continue;
                    } else {
                        unset($flushList[$index]);
                        // the related comp has the fk. so put "this" comp immediately
                        // before it in the list
                        array_splice($flushList, $relatedCompIndex, 0, $currentClass);
                        $index = $relatedCompIndex;
                    }
                } elseif ($rel instanceof \Doctrine1\Relation\LocalKey) {
                    // the related component needs to come before the current component
                    // in the list (since this component holds the fk).

                    if ($relatedCompIndex === false) {
                        array_unshift($flushList, $relatedClassName);
                        $index++;
                    } elseif ($relatedCompIndex <= $index) {
                        // it's already in the right place
                        continue;
                    } else {
                        unset($flushList[$relatedCompIndex]);
                        // "this" comp has the fk. so put the related comp before it
                        // in the list
                        array_splice($flushList, $index, 0, $relatedClassName);
                    }
                } elseif ($rel instanceof \Doctrine1\Relation\Association) {
                    // the association class needs to come after both classes
                    // that are connected through it in the list (since it holds
                    // both fks)

                    $assocTable = $rel->getAssociationFactory();
                    $assocClassName = $assocTable->getComponentName();

                    if ($relatedCompIndex !== false) {
                        unset($flushList[$relatedCompIndex]);
                    }

                    array_splice($flushList, $index, 0, $relatedClassName);
                    $index++;

                    $index3 = array_search($assocClassName, $flushList);

                    if ($index3 === false) {
                        $flushList[] = $assocClassName;
                    } elseif ($index3 >= $index || $relatedCompIndex === false) {
                        continue;
                    } else {
                        unset($flushList[$index3]);
                        array_splice($flushList, $index - 1, 0, $assocClassName);
                        $index = $relatedCompIndex;
                    }
                }
            }
        }

        return array_values($flushList);
    }

    protected function assignSequence(\Doctrine1\Record $record, array &$fields = null): ?int
    {
        $table = $record->getTable();
        $seq = $table->sequenceName;

        if (!empty($seq)) {
            $id = $this->conn->sequence->nextId($seq);
            $seqName = $table->getIdentifier();
            if (is_array($seqName)) {
                throw new \Doctrine1\Exception("Multi column identifiers are not supported in sequences");
            }
            if ($fields) {
                $fields[$seqName] = $id;
            }

            $record->assignIdentifier($id);

            return $id;
        }

        return null;
    }

    protected function assignIdentifier(\Doctrine1\Record $record): void
    {
        $table = $record->getTable();
        $identifier = $table->getIdentifier();
        $seq = $table->sequenceName;

        if (empty($seq) && !is_array($identifier) && $table->getIdentifierType() != IdentifierType::Natural) {
            $id = false;
            if ($record->$identifier == null) {
                if ($driver = $this->conn instanceof Pgsql) {
                    $seq = $table->getTableName() . "_" . $table->getColumnName($identifier);
                }

                $id = $this->conn->sequence->lastInsertId($seq);
            } else {
                $id = $record->$identifier;
            }

            if (!$id) {
                throw new \Doctrine1\Exception("Couldn't get last insert identifier.");
            }
            $record->assignIdentifier($id);
        } else {
            $record->assignIdentifier(true);
        }
    }
}
