<?php

namespace Doctrine1;

/**
 * @template Connection of Connection
 * @extends Connection\Module<Connection>
 */
class Formatter extends Connection\Module
{
    /**
     * Quotes pattern (% and _) characters in a string)
     *
     * EXPERIMENTAL
     *
     * WARNING: this function is experimental and may change signature at
     * any time until labelled as non-experimental
     *
     * @param string $text the input string to quote
     *
     * @return string  quoted string
     */
    public function escapePattern($text)
    {
        if (!$this->conn->string_quoting['escape_pattern']) {
            return $text;
        }
        $tmp = $this->conn->string_quoting;

        $text = str_replace(
            $tmp['escape_pattern'],
            $tmp['escape_pattern'] .
            $tmp['escape_pattern'],
            $text
        );

        foreach ($this->conn->wildcards as $wildcard) {
            $text = str_replace($wildcard, $tmp['escape_pattern'] . $wildcard, $text);
        }
        return $text;
    }

    /**
     * convertBooleans
     * some drivers need the boolean values to be converted into integers
     * when using DQL API
     *
     * This method takes care of that conversion
     *
     * @param array|bool|int|float $item
     *
     * @return mixed[]|bool|int|float
     */
    public function convertBooleans(array|string|bool|int|float|null $item): array|string|bool|int|float|null
    {
        if (is_array($item)) {
            foreach ($item as $k => $value) {
                if (is_bool($value)) {
                    $item[$k] = (int) $value;
                }
            }
        } else {
            if (is_bool($item)) {
                $item = (int) $item;
            }
        }
        return $item;
    }

    /**
     * Quote a string so it can be safely used as a table or column name
     *
     * Delimiting style depends on which database driver is being used.
     *
     * NOTE: just because you CAN use delimited identifiers doesn't mean
     * you SHOULD use them.  In general, they end up causing way more
     * problems than they solve.
     *
     * Portability is broken by using the following characters inside
     * delimited identifiers:
     *   + backtick (<kbd>`</kbd>) -- due to MySQL
     *   + brackets (<kbd>[</kbd> or <kbd>]</kbd>) -- due to Access
     *
     * Delimited identifiers are known to generally work correctly under
     * the following drivers:
     *   + mysql
     *   + mysqli
     *   + pgsql
     *   + sqlite
     *
     * InterBase doesn't seem to be able to use delimited identifiers
     * via PHP 4.  They work fine under PHP 5.
     *
     * @param string $str         identifier name to be quoted
     * @param bool   $checkOption check the 'quote_identifier' option
     *
     * @return string               quoted identifier string
     */
    public function quoteIdentifier($str, $checkOption = true)
    {
        if ($checkOption && !$this->conn->getQuoteIdentifier()) {
            return $str;
        }
        $tmp = $this->conn->identifier_quoting;
        $str = str_replace(
            $tmp['end'],
            $tmp['escape'] .
            $tmp['end'],
            $str
        );

        return $tmp['start'] . $str . $tmp['end'];
    }


    /**
     * quoteMultipleIdentifier
     * Quotes multiple identifier strings
     *
     * @param array $arr         identifiers array to be quoted
     * @param bool  $checkOption check the 'quote_identifier' option
     *
     * @return array
     */
    public function quoteMultipleIdentifier($arr, $checkOption = true)
    {
        foreach ($arr as $k => $v) {
            $arr[$k] = $this->quoteIdentifier($v, $checkOption);
        }

        return $arr;
    }

    /**
     * quotes given input parameter
     *
     * @param  mixed  $input parameter to be quoted
     * @param  string $type
     * @return string|null
     */
    public function quote($input, $type = null)
    {
        if ($type === null) {
            $type = gettype($input);
        }
        switch ($type) {
            case 'integer':
            case 'double':
            case 'float':
            case 'bool':
            case 'decimal':
            case 'int':
                return $input;
            case 'array':
            case 'object':
                $input = serialize($input);
                // no break
            case 'date':
            case 'time':
            case 'timestamp':
            case 'string':
            case 'char':
            case 'varchar':
            case 'text':
            case 'blob':
            case 'enum':
            case 'set':
            case 'boolean':
                return "'" . str_replace("'", "''", $input) . "'";
        }

        return null;
    }

    /**
     * Removes any formatting in an sequence name using the 'seqname_format' option
     *
     * @param  string $sqn string that containts name of a potential sequence
     * @return string name of the sequence with possible formatting removed
     */
    public function fixSequenceName($sqn)
    {
        $seqPattern = '/^' . preg_replace('/%s/', '([a-z0-9_]+)', $this->conn->getSequenceNameFormat()) . '$/i';
        $seqName    = preg_replace($seqPattern, '\\1', $sqn);

        if ($seqName && !strcasecmp($sqn, $this->getSequenceName($seqName))) {
            return $seqName;
        }
        return $sqn;
    }

    /**
     * Removes any formatting in an index name using the 'idxname_format' option
     *
     * @param  string $idx string that containts name of anl index
     * @return string name of the index with possible formatting removed
     */
    public function fixIndexName($idx)
    {
        $indexPattern = '/^' . preg_replace('/%s/', '([a-z0-9_]+)', $this->conn->getIndexNameFormat()) . '$/i';
        $indexName    = preg_replace($indexPattern, '\\1', $idx);
        if ($indexName && !strcasecmp($idx, $this->getIndexName($indexName))) {
            return $indexName;
        }
        return $idx;
    }

    /**
     * adds sequence name formatting to a sequence name
     *
     * @param  string $sqn name of the sequence
     * @return string   formatted sequence name
     */
    public function getSequenceName($sqn)
    {
        return sprintf(
            $this->conn->getSequenceNameFormat(),
            preg_replace('/[^a-z0-9_\$.]/i', '_', $sqn)
        );
    }

    /**
     * adds index name formatting to a index name
     *
     * @param  string $idx name of the index
     * @return string   formatted index name
     */
    public function getIndexName($idx)
    {
        return sprintf(
            $this->conn->getIndexNameFormat(),
            preg_replace('/[^a-z0-9_\$]/i', '_', $idx)
        );
    }

    /**
     * Formatting a foreign Key name
     *
     * @param  string $fkey name of the foreign key
     * @return string   formatted foreign key name
     */
    public function getForeignKeyName($fkey)
    {
        return sprintf(
            $this->conn->getForeignKeyNameFormat(),
            preg_replace('/[^a-z0-9_\$]/i', '_', $fkey)
        );
    }
}
