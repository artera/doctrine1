<?php

class Doctrine_Validator_Unique extends Doctrine_Validator_Driver
{
    /**
     * checks if given value is unique
     *
     * @param  mixed $value
     * @return boolean
     */
    public function validate($value)
    {
        if (is_null($value)) {
            return true;
        }

        /** @var Doctrine_Table */
        $table = $this->invoker->getTable();
        $conn  = $table->getConnection();
        $pks   = $table->getIdentifierColumnNames();

        if (is_array($pks)) {
            for ($i = 0, $l = count($pks); $i < $l; $i++) {
                $pks[$i] = $conn->quoteIdentifier($pks[$i]);
            }

            $pks = implode(', ', $pks);
        }

        $sql = 'SELECT ' . $pks . ' FROM ' . $conn->quoteIdentifier($table->getTableName()) . ' WHERE ';

        if (is_array($this->field)) {
            foreach ($this->field as $k => $v) {
                $this->field[$k] = $conn->quoteIdentifier($table->getColumnName($v));
            }

            $sql .= implode(' = ? AND ', $this->field) . ' = ?';
            $values = $value;
        } else {
            $sql .= $conn->quoteIdentifier($table->getColumnName($this->field)) . ' = ?';
            $values   = [];
            $values[] = $value;
        }

        // If the record is not new we need to add primary key checks because its ok if the
        // unique value already exists in the database IF the record in the database is the same
        // as the one that is validated here.
        $state = $this->invoker->state();
        if (!$state->isTransient()) {
            foreach ((array) $table->getIdentifierColumnNames() as $pk) {
                $sql .= ' AND ' . $conn->quoteIdentifier($pk) . ' != ?';
                $pkFieldName = $table->getFieldName($pk);
                $values[]    = $this->invoker->$pkFieldName;
            }
        }

        if (isset($this->args) && is_array($this->args) && isset($this->args['where'])) {
            $sql .= ' AND ' . $this->args['where'];
        }

        /** @var Doctrine_Connection_Statement */
        $stmt = $table->getConnection()->getDbh()->prepare($sql);
        $stmt->execute($values);

        return !is_array($stmt->fetch());
    }
}
