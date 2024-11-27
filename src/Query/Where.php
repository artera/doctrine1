<?php

namespace Doctrine1\Query;

class Where extends \Doctrine1\Query\Condition
{
    public function load(string $where): string
    {
        // Handle operator ("AND" | "OR"), reducing overhead of this method processment
        $possibleOp = strtolower($where);

        if ($possibleOp == 'and' || $possibleOp == 'or') {
            return $where;
        }

        $where = $this->tokenizer->bracketTrim(trim($where));
        $conn  = $this->query->getConnection();
        $terms = $this->tokenizer->sqlExplode($where);

        if (count($terms) > 1) {
            if (substr($where, 0, 6) == 'EXISTS') {
                return $this->parseExists($where, true);
            } elseif (preg_match('/^NOT\s+EXISTS\b/i', $where) !== 0) {
                return $this->parseExists($where, false);
            }
        }

        if (count($terms) < 3) {
            $terms = $this->tokenizer->sqlExplode($where, ['=', '<', '<>', '>', '!=']);
        }

        if (count($terms) > 1) {
            $leftExpr  = array_shift($terms);
            $rightExpr = array_pop($terms);
            assert($rightExpr !== null);
            $operator  = trim(substr($where, strlen($leftExpr), -strlen($rightExpr)));

            if (strpos($leftExpr, "'") === false && strpos($leftExpr, '(') === false) {
                // normal field reference found
                $a         = explode('.', $leftExpr);
                $fieldname = array_pop($a); // Discard the field name (not needed!)
                $reference = implode('.', $a);

                if (empty($reference)) {
                    $map   = $this->query->getRootDeclaration();
                    $alias = $this->query->getSqlTableAlias($this->query->getRootAlias());
                } else {
                    $map   = $this->query->load($reference, false);
                    $alias = $this->query->getSqlTableAlias($reference);
                }
            }

            $sql = $this->buildSql($leftExpr, $operator, $rightExpr);

            return $sql;
        } else {
            return $where;
        }
    }

    protected function buildSql(string $leftExpr, string $operator, string $rightExpr): string
    {
        $leftExprOriginal = $leftExpr;
        $leftExpr         = $this->query->parseClause($leftExpr);

        // BETWEEN operation
        if ('BETWEEN' == strtoupper(substr($operator, 0, 7))) {
            $midExpr  = trim(substr($operator, 7, -3));
            $operator = 'BETWEEN ' . $this->query->parseClause($midExpr) . ' AND';
        }

        // NOT BETWEEN operation
        if ('NOT BETWEEN' == strtoupper(substr($operator, 0, 11))) {
            $midExpr  = trim(substr($operator, 11, -3));
            $operator = 'NOT BETWEEN ' . $this->query->parseClause($midExpr) . ' AND';
        }

        $op    = strtolower($operator);
        $isInX = ($op == 'in' || $op == 'not in');

        // Check if we are not dealing with "obj.field IN :named"
        if (substr($rightExpr, 0, 1) == ':' && $isInX) {
            throw new \Doctrine1\Query\Exception(
                'Cannot use ' . $operator . ' with a named parameter in "' .
                $leftExprOriginal . ' ' . $operator . ' ' . $rightExpr . '"'
            );
        }

        // Right Expression
        $rightExpr = ($rightExpr == '?' && $isInX)
            ? $this->buildWhereInArraySqlPart($rightExpr)
            : $this->query->parseClause($rightExpr);

        return $leftExpr . ' ' . $operator . ' ' . $rightExpr;
    }


    protected function buildWhereInArraySqlPart(string $rightExpr): string
    {
        $params = $this->query->getInternalParams();
        $value  = [];

        for ($i = 0, $l = count($params); $i < $l; $i++) {
            if (is_array($params[$i])) {
                $value = array_fill(0, count($params[$i]), $rightExpr);
                $this->query->adjustProcessedParam($i);

                break;
            }
        }

        return '(' . (count($value) > 0 ? implode(', ', $value) : $rightExpr) . ')';
    }

    /**
     * parses an EXISTS expression
     *
     * @param  string  $where    query where part to be parsed
     * @param  boolean $negation whether or not to use the NOT keyword
     */
    public function parseExists(string $where, bool $negation): string
    {
        $operator = ($negation) ? 'EXISTS' : 'NOT EXISTS';

        $pos = strpos($where, '(');

        if ($pos == false) {
            throw new \Doctrine1\Query\Exception('Unknown expression, expected a subquery with () -marks');
        }

        $sub = $this->tokenizer->bracketTrim(substr($where, $pos));

        $q   = $this->query->createSubquery()->parseDqlQuery($sub, false);
        $sql = $q->getSqlQuery();
        $q->free();

        return $operator . ' (' . $sql . ')';
    }
}
