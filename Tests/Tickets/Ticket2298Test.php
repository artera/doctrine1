<?php
namespace Tests\Tickets;

use Tests\DoctrineUnitTestCase;

class Ticket2298Test extends DoctrineUnitTestCase
{
    public function testTokenizerIgnoresQuotes()
    {
        $q = \Doctrine1\Query::create()
            ->from('Address a')
            ->where("a.address = '(a) and c'");
        $this->assertEquals($q->getSqlQuery(), "SELECT a.id AS a__id, a.address AS a__address FROM address a WHERE (a.address = '(a) and c')");

        $q = \Doctrine1\Query::create()
            ->from('Address a')
            ->where("a.address = ' or what'");
        $this->assertEquals($q->getSqlQuery(), "SELECT a.id AS a__id, a.address AS a__address FROM address a WHERE (a.address = ' or what')");

        $q = \Doctrine1\Query::create()
            ->from('Address a')
            ->where("a.address = ' or      6spaces'");
        $this->assertEquals($q->getSqlQuery(), "SELECT a.id AS a__id, a.address AS a__address FROM address a WHERE (a.address = ' or      6spaces')");
    }


    public function testEscapedQuotes()
    {
        $tokenizer  = new \Doctrine1\Query\Tokenizer();
        $delimiters = [' ', '+', '-', '*', '/', '<', '>', '=', '>=', '<=', '&', '|'];

        $res = $tokenizer->bracketExplode("'a string with AND in the middle'", ' AND ');
        $this->assertEquals($res, ["'a string with AND in the middle'"]);

        $res = $tokenizer->bracketExplode("'o\' AND string'", ' AND ');
        $this->assertEquals($res, ["'o\' AND string'"]);

        $res = $tokenizer->sqlExplode("('John O\'Connor (West) as name'+' ') + 'b'", $delimiters);
        $this->assertEquals($res, ["('John O\'Connor (West) as name'+' ')", '', '', "'b'"]);

        $res = $tokenizer->sqlExplode("'(Word) and' term", $delimiters);
        $this->assertEquals($res, ["'(Word) and'", 'term']);
    }


    public function testAdditionalTokenizerFeatures()
    {
        // These tests all pass with the old tokenizer, they were developed wile
        // working on the patch
        $tokenizer  = new \Doctrine1\Query\Tokenizer();
        $delimiters = [' ', '+', '-', '*', '/', '<', '>', '=', '>=', '<=', '&', '|'];

        $res = $tokenizer->bracketExplode("(age < 20 AND age > 18) AND email LIKE 'John@example.com'", ' AND ', '(', ')');
        $this->assertEquals($res, ['(age < 20 AND age > 18)',"email LIKE 'John@example.com'"]);

        $res = $tokenizer->sqlExplode("sentence OR 'term'", ' OR ');
        $this->assertEquals($res, ['sentence', "'term'"]);

        $res = $tokenizer->clauseExplode("'a + b'+c", $delimiters);
        $this->assertEquals($res, [["'a + b'",'+'], ['c', '']]);

        $res = $tokenizer->quoteExplode('"a"."b"', ' ');
        $this->assertEquals($res, ['"a"."b"']);
    }
}
