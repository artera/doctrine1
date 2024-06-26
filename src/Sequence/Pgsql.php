<?php

namespace Doctrine1\Sequence;

use Doctrine1\Connection\Exception\SyntaxErrorOrAccessRuleViolation\UndefinedTable;

class Pgsql extends \Doctrine1\Sequence
{
    /**
     * Returns the next free id of a sequence
     *
     * @param string $seqName  name of the sequence
     * @param bool   $onDemand when true missing sequences are automatic created
     */
    public function nextId($seqName, $onDemand = true): int
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($seqName), true);
        $query = "SELECT NEXTVAL('" . $sequenceName . "')";

        try {
            $result = (int) $this->conn->fetchOne($query);
        } catch (\Doctrine1\Connection\Exception $e) {
            if ($onDemand && $e instanceof UndefinedTable) {
                try {
                    $result = $this->conn->export->createSequence($seqName);
                } catch (\Doctrine1\Exception $e) {
                    throw new \Doctrine1\Sequence\Exception("On demand sequence $seqName could not be created", previous: $e);
                }

                return $this->nextId($seqName, false);
            } else {
                throw new \Doctrine1\Sequence\Exception("Sequence $seqName does not exist", previous: $e);
            }
        }

        return $result;
    }

    /**
     * lastInsertId
     *
     * Returns the autoincrement ID if supported or $id or fetches the current
     * ID in a sequence called: $table.(empty($field) ? '' : '_'.$field)
     *
     * @param  string $table name of the table into which a new row was inserted
     * @param  string $field name of the field into which a new row was inserted
     */
    public function lastInsertId($table = null, $field = null): string
    {
        $seqName = $table . (empty($field) ? "" : "_" . $field);
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($seqName), true);

        return $this->conn->fetchOne("SELECT CURRVAL('" . $sequenceName . "')");
    }

    /**
     * Returns the current id of a sequence
     *
     * @param string $seqName name of the sequence
     *
     * @return integer          current id in the given sequence
     */
    public function currId($seqName): int
    {
        $sequenceName = $this->conn->quoteIdentifier($this->conn->formatter->getSequenceName($seqName), true);
        return (int) $this->conn->fetchOne("SELECT last_value FROM " . $sequenceName);
    }
}
