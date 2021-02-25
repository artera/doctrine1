<?php

class Doctrine_Query_From extends Doctrine_Query_Part
{
    /**
     * DQL FROM PARSER
     * parses the FROM part of the query string
     *
     * @param  boolean $return if to return the parsed FROM and skip load()
     */
    public function parse(string $str, bool $return = false): ?array
    {
        $str   = trim($str);
        $parts = $this->tokenizer->bracketExplode($str, 'JOIN ');

        $from = $return ? [] : null;

        $operator = false;

        switch (trim($parts[0])) {
            case 'INNER':
                $operator = ':';
                // no break
            case 'LEFT':
                array_shift($parts);
                break;
        }

        $last = '';

        foreach ($parts as $k => $part) {
            $part = trim($part);

            if (empty($part)) {
                continue;
            }

            $e = explode(' ', $part);

            if (end($e) == 'INNER' || end($e) == 'LEFT') {
                $last = array_pop($e);
            }
            $part = implode(' ', $e);

            foreach ($this->tokenizer->bracketExplode($part, ',') as $reference) {
                $reference = trim($reference);
                $e         = explode(' ', $reference);
                $e2        = explode('.', $e[0]);

                if ($operator) {
                    $e[0] = array_shift($e2) . $operator . implode('.', $e2);
                }

                if ($return) {
                    $from[] = $e;
                } else {
                    $table = $this->query->load(implode(' ', $e));
                }
            }

            $operator = ($last == 'INNER') ? ':' : '.';
        }
        return $from;
    }
}
