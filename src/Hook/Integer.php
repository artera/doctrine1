<?php

namespace Doctrine1\Hook;

class Integer extends \Doctrine1\Hook\Parser\Complex
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
        $e = explode(' ', $value);
        $a = [];

        foreach ($e as $v) {
            $v = trim($v);

            $e2 = explode('-', $v);

            $name = $alias . '.' . $field;

            if (count($e2) == 1) {
                // one '-' found

                $a[] = $name . ' = ?';

                $this->params[] = $v;
            } else {
                // more than one '-' found

                $a[] = '(' . $name . ' > ? AND ' . $name . ' < ?)';

                $this->params += [$e2[0], $e2[1]];
            }
        }
        return implode(' OR ', $a);
    }
}
