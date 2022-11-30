<?php

namespace Doctrine1\Query;

class Check
{
    protected \Doctrine1\Table $table;

    /**
     * database specific sql CHECK constraint definition
     * parsed from the given dql CHECK definition
     */
    protected string $sql;

    protected \Doctrine1\Query\Tokenizer $tokenizer;

    /**
     * @param \Doctrine1\Table|string $table \Doctrine1\Table object
     */
    public function __construct(\Doctrine1\Table|string $table)
    {
        if (!($table instanceof \Doctrine1\Table)) {
            $table = \Doctrine1\Manager::getInstance()
                ->getCurrentConnection()
                ->getTable($table);
        }
        $this->table      = $table;
        $this->tokenizer = new \Doctrine1\Query\Tokenizer();
    }

    /**
     * returns the table object associated with this object
     */
    public function getTable(): \Doctrine1\Table
    {
        return $this->table;
    }

    /**
     * @param string $dql DQL CHECK constraint definition
     */
    public function parse(string $dql): void
    {
        $this->sql = $this->parseClause($dql);
    }

    public function parseClause(string $dql): string
    {
        $parts = $this->tokenizer->sqlExplode($dql, ' AND ');

        if (count($parts) > 1) {
            $ret = [];
            foreach ($parts as $part) {
                $ret[] = $this->parseSingle($part);
            }

            $r = implode(' AND ', $ret);
        } else {
            $parts = $this->tokenizer->quoteExplode($dql, ' OR ');
            if (count($parts) > 1) {
                $ret = [];
                foreach ($parts as $part) {
                    $ret[] = $this->parseClause($part);
                }

                $r = implode(' OR ', $ret);
            } else {
                $ret = $this->parseSingle($dql);
                return $ret;
            }
        }
        return '(' . $r . ')';
    }

    public function parseSingle(string $part): string
    {
        $e = explode(' ', $part);

        $e[0] = $this->parseFunction($e[0]);

        switch ($e[1]) {
            case '>':
            case '<':
            case '=':
            case '!=':
            case '<>':
                break;
            default:
                throw new \Doctrine1\Query\Exception('Unknown operator ' . $e[1]);
        }

        return implode(' ', $e);
    }

    public function parseFunction(string $dql): mixed
    {
        if (($pos = strpos($dql, '(')) !== false) {
            $func  = substr($dql, 0, $pos);
            $value = substr($dql, ($pos + 1), -1);

            $expr = $this->table->getConnection()->expression;

            if (!method_exists($expr, $func)) {
                throw new \Doctrine1\Query\Exception('Unknown function ' . $func);
            }

            $func = $expr->$func($value);
        }
        return $func;
    }

    /**
     * returns database specific sql CHECK constraint definition
     * parsed from the given dql CHECK definition
     */
    public function getSql(): string
    {
        return $this->sql;
    }
}
