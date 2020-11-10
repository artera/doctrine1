<?php
namespace Tests\Core;

use Tests\DoctrineUnitTestCase;

class TokenizerTest extends DoctrineUnitTestCase
{
    public static function prepareData(): void
    {
    }
    public static function prepareTables(): void
    {
    }

    public function testSqlExplode()
    {
        $tokenizer = new \Doctrine_Query_Tokenizer();

        $str = 'word1 word2 word3';
        $a   = $tokenizer->sqlExplode($str);

        $this->assertEquals($a, ['word1', 'word2', 'word3']);

        $str = 'word1 (word2 word3)';
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['word1', '(word2 word3)']);

        $str = "word1 'word2 word3'";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['word1', "'word2 word3'"]);

        $str = "word1 'word2 word3'";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['word1', "'word2 word3'"]);

        $str = 'word1 "word2 word3"';
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['word1', '"word2 word3"']);

        $str = 'word1 ((word2) word3)';
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['word1', '((word2) word3)']);

        $str = "word1 ( (word2) 'word3')";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['word1', "( (word2) 'word3')"]);

        $str = "word1 ( \"(word2) 'word3')";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['word1', "( \"(word2) 'word3')"]);

        $str = "word1 ( ''(word2) 'word3')";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['word1', "( ''(word2) 'word3')"]);

        $str = "word1 ( '()()'(word2) 'word3')";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['word1', "( '()()'(word2) 'word3')"]);

        $str = "word1 'word2)() word3'";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['word1', "'word2)() word3'"]);

        $str = 'word1 (word2() word3)';
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['word1', '(word2() word3)']);

        $str = 'word1 "word2)() word3"';
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['word1', '"word2)() word3"']);

        $str = "something (subquery '')";
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['something', "(subquery '')"]);

        $str = 'something (( ))';
        $a   = $tokenizer->sqlExplode($str);
        $this->assertEquals($a, ['something', '(( ))']);
    }

    public function testSqlExplode2()
    {
        $tokenizer = new \Doctrine_Query_Tokenizer();
        $str       = 'rdbms (dbal OR database)';
        $a         = $tokenizer->sqlExplode($str, ' OR ');

        $this->assertEquals($a, ['rdbms (dbal OR database)']);
    }

    public function testBracketExplode()
    {
        $tokenizer = new \Doctrine_Query_Tokenizer();

        $str = 'foo.field AND bar.field';
        $a   = $tokenizer->bracketExplode($str, [' \&\& ', ' AND '], '(', ')');
        $this->assertEquals($a, ['foo.field', 'bar.field']);

        // delimiters should be case insensitive
        $str = 'foo.field and bar.field';
        $a   = $tokenizer->bracketExplode($str, [' \&\& ', ' AND '], '(', ')');
        $this->assertEquals($a, ['foo.field', 'bar.field']);

        // test the JOIN splitter as used in \Doctrine_Query_From::parse()
        $str = 'foo.field join bar.field';
        $a   = $tokenizer->bracketExplode($str, 'JOIN');
        $this->assertEquals($a, ['foo.field', 'bar.field']);

        // test that table names including the split string are unaffected
        $str = 'foojointable.field join bar.field';
        $a   = $tokenizer->bracketExplode($str, 'JOIN');
        $this->assertEquals($a, ['foojointable.field', 'bar.field']);
    }

    public function testQuoteExplodedShouldQuoteArray()
    {
        $tokenizer = new \Doctrine_Query_Tokenizer();
        $term      = $tokenizer->quoteExplode('test', ["'test'", 'test2']);
        $this->assertEquals($term[0], 'test');
    }
}
