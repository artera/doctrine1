<?php

namespace Doctrine1\Query;

class Groupby extends \Doctrine1\Query\Part
{
    /**
     * DQL GROUP BY PARSER
     * parses the group by part of the query string
     */
    public function parse(string $clause, bool $append = false): string
    {
        $terms = $this->tokenizer->clauseExplode($clause, [' ', '+', '-', '*', '/', '<', '>', '=', '>=', '<=']);
        $str   = '';

        foreach ($terms as $term) {
            $pos      = strpos($term[0], '(');
            $hasComma = false;

            if ($pos !== false) {
                $name = substr($term[0], 0, $pos);

                $term[0] = $this->query->parseFunctionExpression($term[0]);
            } else {
                if (substr($term[0], 0, 1) !== "'" && substr($term[0], -1) !== "'") {
                    if (strpos($term[0], '.') !== false) {
                        if (!is_numeric($term[0])) {
                            $e = explode('.', $term[0]);

                            $field = array_pop($e);

                            // Check if field name still has comma
                            if (($pos = strpos($field, ',')) !== false) {
                                $field    = substr($field, 0, $pos);
                                $hasComma = true;
                            }

                            // Grab query connection
                            $conn = $this->query->getConnection();

                            if ($this->query->getType()->isSelect()) {
                                $componentAlias = implode('.', $e);

                                if (empty($componentAlias)) {
                                    $componentAlias = $this->query->getRootAlias();
                                }

                                $this->query->load($componentAlias);

                                // check the existence of the component alias
                                $queryComponent = $this->query->getQueryComponent($componentAlias);

                                $table = $queryComponent['table'];

                                $def = $table->getDefinitionOf($field);

                                // get the actual field name from alias
                                $field = $table->getColumnName($field);

                                // check column existence
                                if (!$def) {
                                    throw new \Doctrine1\Query\Exception('Unknown column ' . $field);
                                }

                                if (isset($def['owner'])) {
                                    $componentAlias = $componentAlias . '.' . $def['owner'];
                                }

                                $tableAlias = $this->query->getSqlTableAlias($componentAlias);

                                // build sql expression
                                $term[0] = $conn->quoteIdentifier($tableAlias) . '.' . $conn->quoteIdentifier($field);
                            } else {
                                // build sql expression
                                $field   = $this->query->getRoot()->getColumnName($field);
                                $term[0] = $conn->quoteIdentifier($field);
                            }
                        }
                    } else {
                        if (!empty($term[0])
                            && !is_numeric($term[0])
                            && $term[0] !== '?' && substr($term[0], 0, 1) !== ':'
                        ) {
                            $componentAlias = $this->query->getRootAlias();

                            $found = false;

                            // Check if field name still has comma
                            if (($pos = strpos($term[0], ',')) !== false) {
                                $term[0]  = substr($term[0], 0, $pos);
                                $hasComma = true;
                            }

                            if ($componentAlias !== false
                                && $componentAlias !== null
                            ) {
                                $queryComponent = $this->query->getQueryComponent($componentAlias);

                                $table = $queryComponent['table'];

                                // check column existence
                                if ($table->hasField($term[0])) {
                                    $found = true;

                                    $def = $table->getDefinitionOf($term[0]);

                                    // get the actual column name from field name
                                    $term[0] = $table->getColumnName($term[0]);


                                    if (isset($def['owner'])) {
                                        $componentAlias = $componentAlias . '.' . $def['owner'];
                                    }

                                    $tableAlias = $this->query->getSqlTableAlias($componentAlias);
                                    $conn       = $this->query->getConnection();

                                    if ($this->query->getType()->isSelect()) {
                                        // build sql expression
                                        $term[0] = $conn->quoteIdentifier($tableAlias)
                                                 . '.' . $conn->quoteIdentifier($term[0]);
                                    } else {
                                        // build sql expression
                                        $term[0] = $conn->quoteIdentifier($term[0]);
                                    }
                                } else {
                                    $found = false;
                                }
                            }

                            if (!$found) {
                                $term[0] = $this->query->getSqlAggregateAlias($term[0]);
                            }
                        }
                    }
                }
            }

            $str .= $term[0] . ($hasComma ? ',' : '') . $term[1];
        }

        return $str;
    }
}
