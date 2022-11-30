<?php

namespace Doctrine1\Hook;

class WordLike extends \Doctrine1\Hook\Parser\Complex
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
     * @return string
     */
    public function parseSingle($alias, $field, $value)
    {
        $a = [];

        if (strpos($value, "'") !== false) {
            $value = $this->tokenizer->bracketTrim($value, "'", "'");

            $a[]            = $alias . '.' . $field . ' LIKE ?';
            $this->params[] = '%' . $value . '%';
        } else {
            $e2 = explode(' ', $value);

            foreach ($e2 as $v) {
                $v              = trim($v);
                $a[]            = $alias . '.' . $field . ' LIKE ?';
                $this->params[] = '%' . $v . '%';
            }
        }
        return implode(' OR ', $a);
    }
}
