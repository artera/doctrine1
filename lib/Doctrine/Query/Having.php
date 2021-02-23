<?php

class Doctrine_Query_Having extends Doctrine_Query_Condition
{
    /**
     * DQL Aggregate Function parser
     */
    private function parseAggregateFunction(string $func): string
    {
        $pos = strpos($func, '(');

        // Check for subqueries
        if ($pos === 0 && substr($func, 1, 6) == 'SELECT') {
            // This code is taken from WHERE.php
            $sub = $this->_tokenizer->bracketTrim($func);
            $q   = $this->query->createSubquery()->parseDqlQuery($sub, false);
            $sql = $q->getSqlQuery();
            $q->free();
            return '(' . $sql . ')';
        }

        if ($pos !== false) {
            $funcs = [];

            $name   = substr($func, 0, $pos);
            $func   = substr($func, ($pos + 1), -1);
            $params = $this->_tokenizer->bracketExplode($func, ',', '(', ')');

            foreach ($params as $k => $param) {
                $params[$k] = $this->parseAggregateFunction($param);
            }

            $funcs = $name . '(' . implode(', ', $params) . ')';

            return $funcs;
        } else {
            return $this->_parseAliases($func);
        }
    }

    /**
     * Processes part of the query not being an aggregate function
     */
    private function _parseAliases(mixed $value): string
    {
        if (!is_numeric($value)) {
            $a = explode('.', $value);

            if (count($a) > 1) {
                $field = array_pop($a);
                $ref   = implode('.', $a);
                $map   = $this->query->load($ref, false);
                $field = $map['table']->getColumnName($field);
                $value = $this->query->getConnection()->quoteIdentifier($this->query->getSqlTableAlias($ref) . '.' . $field);
            } else {
                $field = end($a);
                if ($this->query->hasSqlAggregateAlias($field)) {
                    $value = $this->query->getSqlAggregateAlias($field);
                }
            }
        }

        return $value;
    }

    /**
     * returns the parsed query part
     */
    final public function load(string $having): string
    {
        $tokens   = $this->_tokenizer->bracketExplode($having, ' ', '(', ')');
        $part     = $this->parseAggregateFunction(array_shift($tokens));
        $operator = array_shift($tokens);
        $value    = implode(' ', $tokens);

        // check the RHS for aggregate functions
        $value = $this->parseAggregateFunction($value);

        $part .= ' ' . $operator . ' ' . $value;

        return $part;
    }
}
