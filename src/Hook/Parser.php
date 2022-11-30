<?php

namespace Doctrine1\Hook;

abstract class Parser
{
    /**
     * @var string
     */
    protected $condition;

    /**
     * @var array
     */
    protected $params = [];

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * getParams
     * returns the parameters associated with this parser
     *
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * parse
     * Parses given field and field value to DQL condition
     * and parameters. This method should always return
     * prepared statement conditions (conditions that use
     * placeholders instead of literal values).
     *
     * @param  string $alias component alias
     * @param  string $field the field name
     * @param  mixed  $value the value of the field
     * @return void
     */
    abstract public function parse($alias, $field, $value);
}
