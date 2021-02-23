<?php

abstract class Doctrine_Query_Condition extends Doctrine_Query_Part
{
    /**
     * DQL CONDITION PARSER
     * parses the join condition/where/having part of the query string
     */
    public function parse(string $str): string
    {
        $tmp = trim($str);

        $parts = $this->_tokenizer->bracketExplode($str, [' OR '], '(', ')');

        if (count($parts) > 1) {
            $ret = [];
            foreach ($parts as $part) {
                $part  = $this->_tokenizer->bracketTrim($part, '(', ')');
                $ret[] = $this->parse($part);
            }
            $r = implode(' OR ', $ret);
        } else {
            $parts = $this->_tokenizer->bracketExplode($str, [' AND '], '(', ')');

            // Ticket #1388: We need to make sure we're not splitting a BETWEEN ...  AND ... clause
            $tmp = [];

            for ($i = 0, $l = count($parts); $i < $l; $i++) {
                $test = $this->_tokenizer->sqlExplode($parts[$i]);

                if (count($test) == 3 && strtoupper($test[1]) == 'BETWEEN') {
                    $tmp[] = $parts[$i] . ' AND ' . $parts[++$i];
                } elseif (count($test) == 4 && strtoupper($test[1]) == 'NOT' && strtoupper($test[2]) == 'BETWEEN') {
                    $tmp[] = $parts[$i] . ' AND ' . $parts[++$i];
                } else {
                    $tmp[] = $parts[$i];
                }
            }

            $parts = $tmp;
            unset($tmp);

            if (count($parts) > 1) {
                $ret = [];
                foreach ($parts as $part) {
                    $part  = $this->_tokenizer->bracketTrim($part, '(', ')');
                    $ret[] = $this->parse($part);
                }
                $r = implode(' AND ', $ret);
            } else {
                // Fix for #710
                if (substr($parts[0], 0, 1) == '(' && substr($parts[0], -1) == ')') {
                    return $this->parse(substr($parts[0], 1, -1));
                } else {
                    // Processing NOT here
                    if (strtoupper(substr($parts[0], 0, 4)) === 'NOT ' && strtoupper(substr($parts[0], 4, 6)) !== 'EXISTS') {
                        $r = 'NOT (' . $this->parse(substr($parts[0], 4)) . ')';
                    } else {
                        return $this->load($parts[0]);
                    }
                }
            }
        }

        return '(' . $r . ')';
    }

    /**
     * parses a literal value and returns the parsed value
     *
     * boolean literals are parsed to integers
     * components are parsed to associated table aliases
     *
     * @param  string $value literal value to be parsed
     */
    public function parseLiteralValue(string $value): int|string
    {
        // check that value isn't a string
        if (strpos($value, '\'') === false) {
            // parse booleans
            $value = $this->query->getConnection()
                ->dataDict->parseBoolean($value);

            $a = explode('.', (string) $value);

            if (count($a) > 1) {
                // either a float or a component..

                if (!is_numeric($a[0])) {
                    // a component found
                    $field     = array_pop($a);
                    $reference = implode('.', $a);
                    $value     = $this->query->getConnection()->quoteIdentifier(
                        $this->query->getSqlTableAlias($reference) . '.' . $field
                    );
                }
            }
        } else {
            // string literal found
        }

        return $value;
    }
}
