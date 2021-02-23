<?php

class Doctrine_Query_JoinCondition extends Doctrine_Query_Condition
{
    public function load(string $condition): string
    {
        $condition = trim($condition);
        $e         = $this->_tokenizer->sqlExplode($condition);

        foreach ($e as $k => $v) {
            if (!$v) {
                unset($e[$k]);
            }
        }
        $e = array_values($e);

        if (($l = count($e)) > 2) {
            $leftExpr = $this->query->parseClause($e[0]);
            $operator = $e[1];

            if ($l == 4) {
                // FIX: "field NOT IN (XXX)" issue
                // Related to ticket #1329
                $operator .= ' ' . $e[2]; // Glue NOT and IN
                $e[2] = $e[3]; // Move "(XXX)" to previous index

                unset($e[3]); // Remove unused index
            } elseif ($l >= 5) {
                // FIX: "field BETWEEN field2 AND field3" issue
                // Related to ticket #1488
                $e[2] .= ' ' . $e[3] . ' ' . $e[4];

                unset($e[3], $e[4]); // Remove unused indexes
            }

            if (substr(trim($e[2]), 0, 1) != '(') {
                $expr = new Doctrine_Expression($e[2], $this->query->getConnection());
                $e[2] = $expr->getSql();
            }

            // We need to check for agg functions here
            $rightMatches          = [];
            $hasRightAggExpression = $this->_processPossibleAggExpression($e[2], $rightMatches);

            // Defining needed information
            $value = $e[2];

            if (substr($value, 0, 1) == '(') {
                // trim brackets
                $trimmed       = $this->_tokenizer->bracketTrim($value);
                $trimmed_upper = strtoupper($trimmed);

                if (substr($trimmed_upper, 0, 4) == 'FROM' || substr($trimmed_upper, 0, 6) == 'SELECT') {
                    // subquery found
                    $q = $this->query->createSubquery()
                        ->parseDqlQuery($trimmed, false);
                    $value = '(' . $q->getSqlQuery() . ')';
                    $q->free();
                } elseif (substr($trimmed_upper, 0, 4) == 'SQL:') {
                    // Change due to bug "(" XXX ")"
                    //$value = '(' . substr($trimmed, 4) . ')';
                    $value = substr($trimmed, 4);
                } else {
                    // simple in expression found
                    $e     = $this->_tokenizer->sqlExplode($trimmed, ',');
                    $value = [];

                    foreach ($e as $part) {
                        $value[] = $this->parseLiteralValue($part);
                    }

                    $value = '(' . implode(', ', $value) . ')';
                }
            } elseif (!$hasRightAggExpression) {
                // Possible expression found (field1 AND field2)
                // In relation to ticket #1488
                $e     = $this->_tokenizer->bracketExplode($value, [' AND ', ' \&\& '], '(', ')');
                $value = [];

                foreach ($e as $part) {
                    $value[] = $this->parseLiteralValue($part);
                }

                $value = implode(' AND ', $value);
            }

            if ($hasRightAggExpression) {
                $rightExpr = $rightMatches[1] . '(' . $value . ')' . $rightMatches[3];
                $rightExpr = $this->query->parseClause($rightExpr);
            } else {
                $rightExpr = $value;
            }

            $condition = $leftExpr . ' ' . $operator . ' ' . $rightExpr;

            return $condition;
        }

        $parser = new Doctrine_Query_Where($this->query, $this->_tokenizer);

        return $parser->parse($condition);
    }

    protected function _processPossibleAggExpression(string &$expr, array &$matches = []): ?int
    {
        $hasAggExpr = preg_match('/(.*[^\s\(\=])\(([^\)]*)\)(.*)/', $expr, $matches);
        if (!$hasAggExpr) {
            return null;
        }

        $expr = $matches[2];

        // We need to process possible comma separated items
        if (substr(trim($matches[3]), 0, 1) == ',') {
            $xplod = $this->_tokenizer->sqlExplode(trim($matches[3], ' )'), ',');

            $matches[3] = [];

            foreach ($xplod as $part) {
                if ($part != '') {
                    $matches[3][] = $this->parseLiteralValue($part);
                }
            }

            $matches[3] = '), ' . implode(', ', $matches[3]);
        }

        return $hasAggExpr;
    }
}
