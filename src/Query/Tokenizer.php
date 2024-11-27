<?php

namespace Doctrine1\Query;

/**
 * @phpstan-template Term = array{string, string, int}
 */
class Tokenizer
{
    /**
     * Splits the given dql query into an array where keys represent different
     * query part names and values are arrays splitted using sqlExplode method
     *
     * example:
     *
     * parameter:
     *     $query = "SELECT u.* FROM User u WHERE u.name LIKE ?"
     * returns:
     *     array(
     *         'select' => 'u.* ',
     *         'from'   => 'User u ',
     *         'where'  => 'u.name LIKE ? '
     *     );
     *
     * @param string $query DQL query
     *
     * @throws \Doctrine1\Query\Exception If some generic parsing error occurs
     *
     * @return array An array containing the query string parts
     * @phpstan-return array<string, string>
     */
    public function tokenizeQuery(string $query): array
    {
        $tokens = $this->sqlExplode($query, ' ');
        $parts  = [];
        $p      = null;

        foreach ($tokens as $index => $token) {
            $token = trim($token);

            switch (strtolower($token)) {
                case 'delete':
                case 'update':
                case 'select':
                case 'set':
                case 'from':
                case 'where':
                case 'limit':
                case 'offset':
                case 'having':
                    $p = $token;
                    $parts[$token] = '';
                    break;

                case 'order':
                case 'group':
                    $i = ($index + 1);
                    if (isset($tokens[$i]) && strtolower($tokens[$i]) === 'by') {
                        $p             = $token;
                        $parts[$token] = '';
                    } else {
                        $parts[$p] .= "$token ";
                    }
                    break;

                case 'by':
                    break;

                default:
                    if ($p === null) {
                        throw new \Doctrine1\Query\Tokenizer\Exception(
                            "Couldn't tokenize query. Encountered invalid token: '$token'."
                        );
                    }

                    $parts[$p] .= "$token ";
            }
        }

        return $parts;
    }

    /**
     * Trims brackets from string
     *
     * @param string $str String to remove the brackets
     * @param string $e1  First bracket, usually '('
     * @param string $e2  Second bracket, usually ')'
     */
    public function bracketTrim(string $str, string $e1 = '(', string $e2 = ')'): string
    {
        if (substr($str, 0, 1) === $e1 && substr($str, -1) === $e2) {
            return substr($str, 1, -1) ?: '';
        } else {
            return $str;
        }
    }

    /**
     * Explodes a sql expression respecting bracket placement.
     *
     * This method transform a sql expression in an array of simple clauses,
     * while observing the parentheses precedence.
     *
     * Note: bracketExplode always trims the returned pieces
     *
     * <code>
     * $str = (age < 20 AND age > 18) AND email LIKE 'John@example.com'
     * $clauses = $tokenizer->bracketExplode($str, ' AND ', '(', ')');
     * // array("(age < 20 AND age > 18)", "email LIKE 'John@example.com'")
     * </code>
     *
     * @param string       $str String to be bracket exploded
     * @param string|array $d   Delimeter which explodes the string
     * @param string       $e1  First bracket, usually '('
     * @param string       $e2  Second bracket, usually ')'
     * @return string[]
     */
    public function bracketExplode(string $str, string|array $d = ' ', string $e1 = '(', string $e2 = ')'): array
    {
        // Bracket explode has to be case insensitive
        $regexp = $this->getSplitRegExpFromArray((array) $d) . 'i';
        $terms  = $this->clauseExplodeRegExp($str, $regexp, $e1, $e2);

        $res = [];

        // Trim is here for historical reasons
        foreach ($terms as $value) {
            $res[] = trim($value[0]);
        }

        return $res;
    }

    /**
     * Explode quotes from string
     *
     * Note: quoteExplode always trims the returned pieces
     *
     * example:
     *
     * parameters:
     *     $str = email LIKE 'John@example.com'
     *     $d = ' LIKE '
     *
     * would return an array:
     *     array("email", "LIKE", "'John@example.com'")
     *
     * @param string $str String to be quote exploded
     * @param string|array $d   Delimeter which explodes the string
     * @return string[]
     */
    public function quoteExplode(string $str, string|array $d = ' '): array
    {
        // According to the testcases quoteExplode is case insensitive
        $regexp = $this->getSplitRegExpFromArray((array) $d) . 'i';
        $terms  = $this->clauseExplodeCountBrackets($str, $regexp);

        $res = [];

        foreach ($terms as $val) {
            $res[] = trim($val[0]);
        }

        return $res;
    }

    /**
     * Explodes a string into array using custom brackets and
     * quote delimeters
     *
     * Note: sqlExplode trims all returned parts
     *
     * example:
     *
     * parameters:
     *     $str = "(age < 20 AND age > 18) AND name LIKE 'John Doe'"
     *     $d   = ' '
     *     $e1  = '('
     *     $e2  = ')'
     *
     * would return an array:
     *     array(
     *         '(age < 20 AND age > 18)',
     *         'name',
     *         'LIKE',
     *         'John Doe'
     *     );
     *
     * @param string       $str String to be SQL exploded
     * @param string|array $d   Delimeter which explodes the string
     * @param string       $e1  First bracket, usually '('
     * @param string       $e2  Second bracket, usually ')'
     * @return string[]
     */
    public function sqlExplode(string $str, string|array $d = ' ', string $e1 = '(', string $e2 = ')'): array
    {
        $terms = $this->clauseExplode($str, (array) $d, $e1, $e2);
        $res   = [];

        foreach ($terms as $value) {
            $res[] = trim($value[0]);
        }

        return $res;
    }

    /**
     * Explodes a string into array using custom brackets and quote delimeters
     * Each array element is a array of length 2 where the first entry contains
     * the term, and the second entry contains the corresponding delimiter
     *
     * example:
     *
     * parameters:
     *     $str = "(age < 20 AND age > 18) AND name LIKE 'John'+' Doe'"
     *     $d   = array(' ', '+')
     *     $e1  = '('
     *     $e2  = ')'
     *
     * would return an array:
     *     array(
     *         array('(age < 20 AND age > 18)', ' '),
     *         array('AND',  ' '),
     *         array('name', ' '),
     *         array('LIKE', ' '),
     *         array('John', '+'),
     *         array(' Doe', '')
     *     );
     *
     * @param string $str String to be clause exploded
     * @param array  $d   Delimeter which explodes the string
     * @param string $e1  First bracket, usually '('
     * @param string $e2  Second bracket, usually ')'
     * @phpstan-return Term[]
     */
    public function clauseExplode(string $str, array $d, string $e1 = '(', string $e2 = ')'): array
    {
        $regexp = $this->getSplitRegExpFromArray($d);

        return $this->clauseExplodeRegExp($str, $regexp, $e1, $e2);
    }

    /**
     * Builds regular expression for split from array. Return regular
     * expression to be applied
     */
    private function getSplitRegExpFromArray(array $d): string
    {
        foreach ($d as $key => $string) {
            $escapedString = preg_quote($string);
            if (preg_match('#^\w+$#', $string)) {
                $escapedString = "\W$escapedString\W";
            }
            $d[$key] = $escapedString;
        }

        if (in_array(' ', $d)) {
            $d[] = '\s';
        }

        return '#(' . implode('|', $d) . ')#';
    }

    /**
     * Same as clauseExplode, but you give a regexp, which splits the string
     * @phpstan-return Term[]
     */
    private function clauseExplodeRegExp(string $str, string $regexp, string $e1 = '(', string $e2 = ')'): array
    {
        $terms = $this->clauseExplodeCountBrackets($str, $regexp, $e1, $e2);
        $terms = $this->mergeBracketTerms($terms);

        // This is only here to comply with the old function signature
        foreach ($terms as & $val) {
            unset($val[2]);
        }

        return $terms;
    }

    /**
     * this function is like clauseExplode, but it doesn't merge bracket terms
     * @phpstan-return Term[]
     */
    private function clauseExplodeCountBrackets(string $str, string $regexp, string $e1 = '(', string $e2 = ')'): array
    {
        $quoteTerms = $this->quotedStringExplode($str);
        $terms      = [];
        $i          = 0;

        foreach ($quoteTerms as $key => $val) {
            if ($key & 1) { // a quoted string
                // If the last term had no ending delimiter, we append the string to the element,
                // otherwise, we create a new element without delimiter
                if (isset($terms[$i - 1][1]) && $terms[$i - 1][1] == '') {
                    $terms[$i - 1][0] .= $val;
                } else {
                    /** @phpstan-var Term $term */
                    $term = [$val, '', 0];
                    $terms[$i++] = $term;
                }
            } else { // Not a quoted string
                // Do the clause explode
                $subterms = $this->clauseExplodeNonQuoted($val, $regexp);

                foreach ($subterms as &$sub) {
                    $c1 = substr_count($sub[0], $e1);
                    $c2 = substr_count($sub[0], $e2);
                    $sub[2] = $c1 - $c2;
                }

                // If the previous term had no delimiter, merge them
                if ($i > 0 && isset($terms[$i - 1][1]) && $terms[$i - 1][1] == '') {
                    $first = array_shift($subterms);
                    if ($first !== null) {
                        $idx   = $i - 1;

                        if (isset($terms[$idx][0])) {
                            $terms[$idx][0] .= $first[0];
                            $terms[$idx][1] = $first[1];
                            $terms[$idx][2] += $first[2];
                        }
                    }
                }

                $terms = array_merge($terms, $subterms);
                $i += sizeof($subterms);
            }
        }

        return $terms;
    }

    /**
     * Explodes a string by the given delimiters, and counts quotes in every
     * term. This function doesn't respect quoted strings.
     * The returned array contains a array per term. These term array contain
     * the following elemnts:
     * [0] = the term itself
     * [1] = the delimiter splitting this term from the next
     *
     * example:
     *
     * parameters:
     *     $str = "a (b '(c+d))'"
     *     $d = array(' ', '+')
     *
     * returns:
     *     array(
     *        array('a', ' ', 0),
     *        array('(b', ' ', 0),
     *        array("'(c", '+', 0),
     *        array("d))'", '', 0)
     *     );
     * @phpstan-return Term[]
     */
    private function clauseExplodeNonQuoted(string $str, string $regexp): array
    {
        $str  = preg_split($regexp, $str, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        $terms = [];
        $i    = 0;

        foreach ($str as $key => $val) {
            // Every odd entry is a delimiter, so add it to the previous term entry
            if (!($key & 1)) {
                /** @phpstan-var Term $term */
                $term = [$val, '', 0];
                $terms[$i] = $term;
            } else {
                /** @phpstan-var Term[] $terms */
                $terms[$i++][1] = $val;
            }
        }

        return $terms;
    }

    /**
     * This expects input from clauseExplodeNonQuoted.
     * It will go through the result and merges any bracket terms with
     * unbalanced bracket count.
     * Note that only the third parameter in each term is used to get the
     * bracket overhang. This is needed to be able to handle quoted strings
     * wich contain brackets
     *
     * example:
     *
     * parameters:
     *     $terms = array(
     *         array("'a(b'", '+', 0)
     *         array('(2', '+', 1),
     *         array('3)', '-', -1),
     *         array('5', '' , '0')
     *     );
     *
     * would return:
     *     array(
     *         array("'a(b'", '+', 0),
     *         array('(2+3)', '-', 0),
     *         array('5'    , '' , 0)
     *     );
     * @phpstan-return Term[] $terms
     * @phpstan-return Term[]
     */
    private function mergeBracketTerms(array $terms): array
    {
        $res = [];
        $i   = 0;

        foreach ($terms as $val) {
            if (!isset($res[$i])) {
                $res[$i] = $val;
            } else {
                $res[$i][0] .= $res[$i][1] . $val[0];
                $res[$i][1] = $val[1];
                $res[$i][2] += $val[2];
            }

            // Bracket overhang
            if ($res[$i][2] == 0) {
                $i++;
            }
        }

        return $res;
    }


    /**
     * Explodes the given string by <quoted words>
     *
     * example:
     *
     * paramters:
     *     $str ="'a' AND name = 'John O\'Connor'"
     *
     * returns
     *     array("", "'a'", " AND name = ", "'John O\'Connor'")
     *
     * Note the trailing empty string. In the result, all even elements are quoted strings.
     *
     * @param string $str the string to split
     * @return string[]
     */
    public function quotedStringExplode(string $str): array
    {
        // Split by all possible incarnations of a quote
        $split = array_map('preg_quote', ["\\'","''","'", '\\"', '""', '"']);
        $split = '#(' . implode('|', $split) . ')#';
        $str   = preg_split($split, $str, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];

        $parts = [];
        $mode  = false; // Mode is either ' or " if the loop is inside a string quoted with ' or "
        $i     = 0;

        foreach ($str as $key => $val) {
            // This is some kind of quote
            if ($key & 1) {
                if (!$mode) {
                    if ($val == "'" || $val == '"') {
                        $mode = $val;
                        $i++;
                    }
                } elseif ($mode == $val) {
                    if (!isset($parts[$i])) {
                        $parts[$i] = $val;
                    } else {
                        $parts[$i] .= $val;
                    }

                    $mode = false;
                    $i++;

                    continue;
                }
            }

            if (!isset($parts[$i])) {
                $parts[$i] = $val;
            } else {
                $parts[$i] .= $val;
            }
        }

        return $parts;
    }
}
