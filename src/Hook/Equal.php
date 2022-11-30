<?php

namespace Doctrine1\Hook;

class Equal extends \Doctrine1\Hook\Parser
{
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
    public function parse($alias, $field, $value)
    {
        $this->params    = (array) $value;
        $this->condition = $alias . '.' . $field . ' = ?';
    }
}
