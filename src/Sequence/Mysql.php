<?php

namespace Doctrine1\Sequence;

class Mysql extends \Doctrine1\Sequence
{
    /**
     * Returns the next free id of a sequence
     *
     * @param string $seqName  name of the sequence
     * @param bool   $onDemand when true missing sequences are automatic created
     */
    public function nextId($seqName, $onDemand = true): int
    {
        $sequenceName = $this->conn->quoteIdentifier($seqName, true);
        $seqcolName   = $this->conn->quoteIdentifier($this->conn->getSequenceColumnName(), true);
        $query        = 'INSERT INTO ' . $sequenceName . ' (' . $seqcolName . ') VALUES (NULL)';

        try {
            $this->conn->exec($query);
        } catch (\Doctrine1\Connection\Exception $e) {
            if ($onDemand && $e->getPortableCode() == \Doctrine1\Core::ERR_NOSUCHTABLE) {
                // Since we are creating the sequence on demand
                // we know the first id = 1 so initialize the
                // sequence at 2
                try {
                    $this->conn->export->createSequence($seqName, 2);
                } catch (\Doctrine1\Exception $e) {
                    throw new \Doctrine1\Sequence\Exception("On demand sequence $seqName could not be created", previous: $e);
                }

                // First ID of a newly created sequence is 1
                return 1;
            } else {
                throw new \Doctrine1\Sequence\Exception("Sequence $seqName does not exist", previous: $e);
            }
        }

        $value = (int) $this->lastInsertId();

        if (is_numeric($value)) {
            $query = 'DELETE FROM ' . $sequenceName . ' WHERE ' . $seqcolName . ' < ' . $value;
            $this->conn->exec($query);
        }

        return $value;
    }

    /**
     * Returns the autoincrement ID if supported or $id or fetches the current
     * ID in a sequence called: $table.(empty($field) ? '' : '_'.$field)
     *
     * @param string $table name of the table into which a new row was inserted
     * @param string $field name of the field into which a new row was inserted
     */
    public function lastInsertId($table = null, $field = null): string|false
    {
        return $this->conn->getDbh()->lastInsertId();
    }

    /**
     * Returns the current id of a sequence
     *
     * @param string $seqName name of the sequence
     */
    public function currId($seqName): int
    {
        $sequenceName = $this->conn->quoteIdentifier($seqName, true);
        $seqcolName   = $this->conn->quoteIdentifier($this->conn->getSequenceColumnName(), true);
        $query        = 'SELECT MAX(' . $seqcolName . ') FROM ' . $sequenceName;

        return (int) $this->conn->fetchOne($query);
    }
}
