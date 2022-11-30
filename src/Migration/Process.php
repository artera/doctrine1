<?php

namespace Doctrine1\Migration;

class Process
{
    /**
     * @var \Doctrine1\Migration
     */
    protected $migration;

    public function __construct(\Doctrine1\Migration $migration)
    {
        $this->migration = $migration;
    }

    /**
     * @return \Doctrine1\Connection
     */
    public function getConnection()
    {
        return $this->migration->getConnection();
    }

    /**
     * Process a created table change
     *
     * @param  array $table Table definition
     * @return void
     */
    public function processCreatedTable(array $table)
    {
        $this->getConnection()->export->createTable($table['tableName'], $table['fields'], $table['options']);
    }

    /**
     * Process a dropped table change
     *
     * @param  array $table Table definition
     * @return void
     */
    public function processDroppedTable(array $table)
    {
        $this->getConnection()->export->dropTable($table['tableName']);
    }

    /**
     * Process a renamed table change
     *
     * @param  array $table Renamed table definition
     * @return void
     */
    public function processRenamedTable(array $table)
    {
        $this->getConnection()->export->alterTable($table['oldTableName'], ['name' => $table['newTableName']]);
    }

    /**
     * Process a created column change
     *
     * @param  array $column Column definition
     * @return void
     */
    public function processCreatedColumn(array $column)
    {
        $this->getConnection()->export->alterTable($column['tableName'], ['add' => [$column['columnName'] => $column]]);
    }

    /**
     * Process a dropped column change
     *
     * @param  array $column Column definition
     * @return void
     */
    public function processDroppedColumn(array $column)
    {
        $this->getConnection()->export->alterTable($column['tableName'], ['remove' => [$column['columnName'] => []]]);
    }

    /**
     * Process a renamed column change
     *
     * @param  array $column Column definition
     * @return void
     */
    public function processRenamedColumn(array $column)
    {
        $columnList = $this->getConnection()->import->listTableColumns($column['tableName']);
        if (isset($columnList[$column['oldColumnName']])) {
            $this->getConnection()->export->alterTable($column['tableName'], ['rename' => [$column['oldColumnName'] => ['name' => $column['newColumnName'], 'definition' => $columnList[$column['oldColumnName']]]]]);
        }
    }

    /**
     * Process a changed column change
     *
     * @param  array $column Changed column definition
     * @return void
     */
    public function processChangedColumn(array $column)
    {
        $options         = [];
        $options         = $column['options'];
        $options['type'] = $column['type'];

        $this->getConnection()->export->alterTable($column['tableName'], ['change' => [$column['columnName'] => ['definition' => $options]]]);
    }

    /**
     * Process a created index change
     *
     * @param  array $index Index definition
     * @return void
     */
    public function processCreatedIndex(array $index)
    {
        $this->getConnection()->export->createIndex($index['tableName'], $index['indexName'], $index['definition']);
    }

    /**
     * Process a dropped index change
     *
     * @param  array $index Index definition
     * @return void
     */
    public function processDroppedIndex(array $index)
    {
        $this->getConnection()->export->dropIndex($index['tableName'], $index['indexName']);
    }

    /**
     * Process a created constraint change
     *
     * @param  array $constraint Constraint definition
     * @return void
     */
    public function processCreatedConstraint(array $constraint)
    {
        $this->getConnection()->export->createConstraint($constraint['tableName'], $constraint['constraintName'], $constraint['definition']);
    }

    /**
     * Process a dropped constraint change
     *
     * @param  array $constraint Constraint definition
     * @return void
     */
    public function processDroppedConstraint(array $constraint)
    {
        $this->getConnection()->export->dropConstraint($constraint['tableName'], $constraint['constraintName'], isset($constraint['definition']['primary']) && $constraint['definition']['primary']);
    }

    /**
     * Process a created foreign key change
     *
     * @param  array $foreignKey Foreign key definition
     * @return void
     */
    public function processCreatedForeignKey(array $foreignKey)
    {
        $this->getConnection()->export->createForeignKey($foreignKey['tableName'], $foreignKey['definition']);
    }

    /**
     * Process a dropped foreign key change
     *
     * @param  array $foreignKey
     * @return void
     */
    public function processDroppedForeignKey(array $foreignKey)
    {
        $this->getConnection()->export->dropForeignKey($foreignKey['tableName'], $foreignKey['definition']['name']);
    }
}
