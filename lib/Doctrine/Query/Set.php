<?php

class Doctrine_Query_Set extends Doctrine_Query_Part
{
    public function parse(string $dql): string
    {
        $terms            = $this->tokenizer->sqlExplode($dql, ' ');
        $termsTranslation = [];

        foreach ($terms as $term) {
            $termOriginal = $term;

            // We need to check for agg functions here
            $matches          = [];
            $hasAggExpression = $this->processPossibleAggExpression($term, $matches);

            $lftExpr = (($hasAggExpression) ? $matches[1] . '(' : '');
            $rgtExpr = (($hasAggExpression) ? $matches[3] . ')' : '');

            preg_match_all("/^([a-zA-Z0-9_]+[\.[a-zA-Z0-9_]+]*)(\sAS\s[a-zA-Z0-9_]+)?/i", $term, $m, PREG_SET_ORDER);

            if (isset($m[0])) {
                $processed = [];

                foreach ($m as $piece) {
                    $part = $piece[1];
                    $e    = explode('.', trim($part));

                    $fieldName = array_pop($e);
                    $reference = (count($e) > 0) ? implode('.', $e) : $this->query->getRootAlias();
                    $aliasMap  = $this->query->getQueryComponent($reference);

                    if ($aliasMap['table']->hasField($fieldName)) {
                        $columnName = $aliasMap['table']->getColumnName($fieldName);
                        $columnName = $aliasMap['table']->getConnection()->quoteIdentifier($columnName);

                        $part = $columnName;
                    }

                    $processed[] = $part . (isset($piece[2]) ? $piece[2] : '');
                }

                $termsTranslation[$termOriginal] = $lftExpr . implode(' ', $processed) . $rgtExpr;
            }
        }

        return strtr($dql, $termsTranslation);
    }

    protected function processPossibleAggExpression(string &$expr, array &$matches = []): ?int
    {
        $hasAggExpr = preg_match('/(.*[^\s\(\=])\(([^\)]*)\)(.*)/', $expr, $matches);
        if (!$hasAggExpr) {
            return null;
        }

        $expr = $matches[2];

        // We need to process possible comma separated items
        if (substr(trim($matches[3]), 0, 1) == ',') {
            $xplod = $this->tokenizer->sqlExplode(trim($matches[3], ' )'), ',');

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
