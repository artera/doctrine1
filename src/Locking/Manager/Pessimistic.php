<?php

namespace Doctrine1\Locking\Manager;

use PDOException;

class Pessimistic
{
    /**
     * The conn that is used by the locking manager
     *
     * @var \Doctrine1\Connection
     */
    private $conn;

    /**
     * The database table name for the lock tracking
     *
     * @var string
     */
    private $lockTable = 'doctrine_lock_tracking';

    /**
     * Constructs a new locking manager object
     *
     * When the CREATE_TABLES attribute of the connection on which the manager
     * is supposed to work on is set to true, the locking table is created.
     *
     * @param \Doctrine1\Connection $conn The database connection to use
     */
    public function __construct(\Doctrine1\Connection $conn)
    {
        $this->conn = $conn;

        if ($this->conn->getExportFlags() & \Doctrine1\Core::EXPORT_TABLES) {
            $columns                = [];
            $columns['object_type'] = ['type'           => 'string',
                                                   'length'  => 50,
                                                   'notnull' => true,
                                                   'primary' => true];

            $columns['object_key'] = ['type'            => 'string',
                                                   'length'  => 250,
                                                   'notnull' => true,
                                                   'primary' => true];

            $columns['user_ident'] = ['type'            => 'string',
                                                   'length'  => 50,
                                                   'notnull' => true];

            $columns['timestamp_obtained'] = ['type'    => 'integer',
                                                   'length'  => 10,
                                                   'notnull' => true];

            $options = ['primary' => ['object_type', 'object_key']];
            try {
                $this->conn->export->createTable($this->lockTable, $columns, $options);
            } catch (\Throwable $e) {
            }
        }
    }

    /**
     * Obtains a lock on a {@link \Doctrine1\Record}
     *
     * @param  \Doctrine1\Record $record    The record that has to be locked
     * @param  mixed           $userIdent A unique identifier of the locking user
     * @return boolean  TRUE if the locking was successful, FALSE if another user
     *                  holds a lock on this record
     * @throws \Doctrine1\Locking\Exception  If the locking failed due to database errors
     */
    public function getLock(\Doctrine1\Record $record, $userIdent)
    {
        $objectType = $record->getTable()->getComponentName();
        $key        = $record->getTable()->getIdentifier();

        $gotLock = false;
        $time    = time();

        if (is_array($key)) {
            // Composite key
            $key = implode('|', $key);
        }

        $dbh = $this->conn->getDbh();
        $savepoint = $this->conn->beginTransaction();

        try {
            $stmt = $dbh->prepare(
                'INSERT INTO ' . $this->lockTable
                                  . ' (object_type, object_key, user_ident, timestamp_obtained)'
                . ' VALUES (:object_type, :object_key, :user_ident, :ts_obtained)'
            );

            $stmt->bindParam(':object_type', $objectType);
            $stmt->bindParam(':object_key', $key);
            $stmt->bindParam(':user_ident', $userIdent);
            $stmt->bindParam(':ts_obtained', $time);

            try {
                $stmt->execute();
                $gotLock = true;

                // we catch an Exception here instead of PDOException since we might also be catching \Doctrine1\Exception
            } catch (\Throwable $pkviolation) {
                // PK violation occured => existing lock!
            }

            if (!$gotLock) {
                $lockingUserIdent = $this->getLockingUserIdent($objectType, $key);
                if ($lockingUserIdent !== null && $lockingUserIdent == $userIdent) {
                    $gotLock = true; // The requesting user already has a lock
                    // Update timestamp
                    $stmt = $dbh->prepare(
                        'UPDATE ' . $this->lockTable
                                          . ' SET timestamp_obtained = :ts'
                                          . ' WHERE object_type = :object_type AND'
                                          . ' object_key  = :object_key  AND'
                        . ' user_ident  = :user_ident'
                    );
                    $stmt->bindParam(':ts', $time);
                    $stmt->bindParam(':object_type', $objectType);
                    $stmt->bindParam(':object_key', $key);
                    $stmt->bindParam(':user_ident', $lockingUserIdent);
                    $stmt->execute();
                }
            }
        } catch (\Throwable $pdoe) {
            $savepoint->rollback();
            throw new \Doctrine1\Locking\Exception($pdoe->getMessage());
        }

        $savepoint->commit();
        return $gotLock;
    }

    /**
     * Releases a lock on a {@link \Doctrine1\Record}
     *
     * @param  \Doctrine1\Record $record    The record for which the lock has to be released
     * @param  mixed           $userIdent The unique identifier of the locking user
     * @return boolean  TRUE if a lock was released, FALSE if no lock was released
     * @throws \Doctrine1\Locking\Exception If the release procedure failed due to database errors
     */
    public function releaseLock(\Doctrine1\Record $record, $userIdent)
    {
        $objectType = $record->getTable()->getComponentName();
        $key        = $record->getTable()->getIdentifier();

        if (is_array($key)) {
            // Composite key
            $key = implode('|', $key);
        }

        try {
            $dbh  = $this->conn->getDbh();
            $stmt = $dbh->prepare(
                "DELETE FROM $this->lockTable WHERE
                                        object_type = :object_type AND
                                        object_key  = :object_key  AND
                                        user_ident  = :user_ident"
            );
            $stmt->bindParam(':object_type', $objectType);
            $stmt->bindParam(':object_key', $key);
            $stmt->bindParam(':user_ident', $userIdent);
            $stmt->execute();

            $count = $stmt->rowCount();

            return ($count > 0);
        } catch (PDOException $pdoe) {
            throw new \Doctrine1\Locking\Exception($pdoe->getMessage());
        }
    }

    /**
     * Gets the unique user identifier of a lock
     *
     * @param string $objectType The type of the object (component name)
     * @param mixed  $key        The unique key of the object. Can be string or array
     *
     * @return null|scalar The unique user identifier for the specified lock
     *
     * @throws \Doctrine1\Locking\Exception If the query failed due to database errors
     */
    private function getLockingUserIdent($objectType, $key)
    {
        if (is_array($key)) {
            // Composite key
            $key = implode('|', $key);
        }

        try {
            $dbh  = $this->conn->getDbh();
            $stmt = $dbh->prepare(
                'SELECT user_ident FROM ' . $this->lockTable
                . ' WHERE object_type = :object_type AND object_key = :object_key'
            );
            $stmt->bindParam(':object_type', $objectType);
            $stmt->bindParam(':object_key', $key);
            $success = $stmt->execute();

            if (!$success) {
                throw new \Doctrine1\Locking\Exception('Failed to determine locking user');
            }

            /** @var scalar|null */
            $userIdent = $stmt->fetchColumn();
        } catch (PDOException $pdoe) {
            throw new \Doctrine1\Locking\Exception($pdoe->getMessage());
        }

        return $userIdent;
    }

    /**
     * Gets the identifier that identifies the owner of the lock on the given
     * record.
     *
     * @param  \Doctrine1\Record $lockedRecord The record.
     * @return mixed The unique user identifier that identifies the owner of the lock.
     */
    public function getLockOwner($lockedRecord)
    {
        $objectType = $lockedRecord->getTable()->getComponentName();
        $key        = $lockedRecord->getTable()->getIdentifier();
        return $this->getLockingUserIdent($objectType, $key);
    }

    /**
     * Releases locks older than a defined amount of seconds
     *
     * When called without parameters all locks older than 15 minutes are released.
     *
     * @param  integer $age        The maximum valid age of locks in seconds
     * @param  string  $objectType The type of the object (component name)
     * @param  mixed   $userIdent  The unique identifier of the locking user
     * @return integer The number of locks that have been released
     * @throws \Doctrine1\Locking\Exception If the release process failed due to database errors
     */
    public function releaseAgedLocks($age = 900, $objectType = null, $userIdent = null)
    {
        $age = time() - $age;

        try {
            $dbh  = $this->conn->getDbh();
            $stmt = $dbh->prepare('DELETE FROM ' . $this->lockTable . ' WHERE timestamp_obtained < :age');
            $stmt->bindParam(':age', $age);
            $query = 'DELETE FROM ' . $this->lockTable . ' WHERE timestamp_obtained < :age';
            if ($objectType) {
                $query .= ' AND object_type = :object_type';
            }
            if ($userIdent) {
                $query .= ' AND user_ident = :user_ident';
            }
            $stmt = $dbh->prepare($query);
            $stmt->bindParam(':age', $age);
            if ($objectType) {
                $stmt->bindParam(':object_type', $objectType);
            }
            if ($userIdent) {
                $stmt->bindParam(':user_ident', $userIdent);
            }
            $stmt->execute();

            $count = $stmt->rowCount();

            return $count;
        } catch (PDOException $pdoe) {
            throw new \Doctrine1\Locking\Exception($pdoe->getMessage());
        }
    }
}
