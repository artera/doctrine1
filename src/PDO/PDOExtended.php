<?php

namespace Doctrine1\PDO;

use PDO;

/**
 * This class extends native PDO one but allow nested transactions
 * by using the SQL statements `SAVEPOINT', 'RELEASE SAVEPOINT' AND 'ROLLBACK SAVEPOINT'
 */
class PDOExtended extends PDO
{
    private int $transactionDepth = 0;

    /**
     * Test if database driver support savepoints
     */
    protected function hasSavepoint(): bool
    {
        return in_array($this->getAttribute(PDO::ATTR_DRIVER_NAME), ["pgsql", "mysql"]);
    }

    /**
     * Start transaction
     */
    public function beginTransaction(): bool
    {
        if ($this->transactionDepth == 0 || !$this->hasSavepoint()) {
            $result = parent::beginTransaction();
        } else {
            $result = $this->exec("SAVEPOINT LEVEL{$this->transactionDepth}") === 0;
        }

        if ($result) {
            $this->transactionDepth++;
        }

        return $result;
    }

    /**
     * Commit current transaction
     */
    public function commit(): bool
    {
        $depth = $this->transactionDepth;

        if ($depth > 0) {
            $depth--;
        }

        if ($depth == 0 || !$this->hasSavepoint()) {
            $result = parent::commit();
        } else {
            $result = $this->exec("RELEASE SAVEPOINT LEVEL$depth") === 0;
        }

        if ($result) {
            $this->transactionDepth = $depth;
        }

        return $result;
    }

    /**
     * Rollback current transaction
     */
    public function rollBack(): bool
    {
        $depth = $this->transactionDepth;

        if ($depth > 0) {
            $depth--;
        }

        if ($depth == 0 || !$this->hasSavepoint()) {
            $result = parent::rollBack();
        } else {
            $result = $this->exec("ROLLBACK TO SAVEPOINT LEVEL$depth") === 0;
        }

        if ($result) {
            $this->transactionDepth = $depth;
        }

        return $result;
    }
}
