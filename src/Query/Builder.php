<?php

namespace Doctrine1\Query;

use Illuminate\Database\Query\Expression;

class Builder extends \Staudenmeir\LaravelCte\Query\Builder
{
    /**
     * Add a select expression to the query.
     *
     * @param  Expression|string  $expression
     * @param  string  $as
     * @return $this
     */
    public function selectExpression($expression, $as)
    {
        if (is_string($expression)) {
            $expression = new Expression($expression);
        }

        return $this->selectRaw('(' . $expression->getValue($this->grammar) . ') as ' . $this->grammar->wrap($as));
    }

    /**
     * Add a select expression to the query.
     *
     * @param  array<string, Expression|string>  $expressions
     * @return $this
     */
    public function selectExpressions(array $expressions)
    {
        foreach ($expressions as $as => $expression) {
            $this->selectExpression($expression, $as);
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function whereRaw($sql, $bindings = [], $boolean = 'and')
    {
        if ($sql instanceof \Illuminate\Contracts\Database\Query\Expression) {
            $sql = $sql->getValue($this->grammar);
        }
        return parent::whereRaw("($sql)", $bindings, $boolean);
    }
}
