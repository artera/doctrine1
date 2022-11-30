<?php

namespace Doctrine1;

/**
 * @template Connection of Connection
 * @extends Connection\Module<Connection>
 */
class Sequence extends Connection\Module
{
    /**
     * @var array
     */
    public $warnings = [];

    /**
     * Returns the next free id of a sequence
     *
     * @param string $seqName  name of the sequence
     * @param bool   $onDemand when true missing sequences are automatic created
     *
     * @return integer          next id in the given sequence
     * @throws Sequence\Exception
     */
    public function nextId($seqName, $onDemand = true): int
    {
        throw new Sequence\Exception('method not implemented');
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
        throw new Sequence\Exception('method not implemented');
    }

    /**
     * Returns the current id of a sequence
     *
     * @param string $seqName name of the sequence
     */
    public function currId($seqName): int
    {
        $this->warnings[] = 'database does not support getting current
            sequence value, the sequence value was incremented';
        return $this->nextId($seqName);
    }
}
